<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Wide Style</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="icon" href="https://i.postimg.cc/CKWyJ3tH/uchoas-2.png" type="image/x-icon">
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    dark: '#0A0A0A',
                    darkgray: '#121212',
                    lightgray: '#A0A0A0',
                },
                fontFamily: {
                    sans: ['Montserrat', 'sans-serif'],
                }
            }
        }
    }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="main.css">

<!-- Supabase JS Library -->
<script type="module">
    import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/+esm'
    window.supabaseCreateClient = createClient;
</script>
</head>
<body class="bg-white dark:bg-black text-black dark:text-white font-sans">
<!-- Notification Container -->
<div id="notification-container"></div>
<div id="promo-banner" class="bg-white dark:bg-black text-black dark:text-white py-2 text-center font-medium border-b border-gray-300 dark:border-gray-800 sticky top-0 z-50 transition-transform duration-300">
    Ganhe 10% OFF com cupom - WIDE10
</div>

<!-- Header -->
<header class="sticky top-0 left-0 w-full bg-white/90 dark:bg-black/90 backdrop-blur-md z-40 transition-colors duration-300">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-20">
            <div class="flex items-center">
                <a href="/" class="flex items-center mr-10">
                    <img src="https://i.postimg.cc/tgYF14JZ/WIDE-STYLE-SITE-BRANCO.png" alt="WIDE STYLE" class="h-14 dark:hidden">
                    <img src="https://i.postimg.cc/QCMM1Byn/WIDE-STYLE-SITE.png" alt="WIDE STYLE" class="h-14 hidden dark:block">
                </a>
            </div>
            
            <div class="hidden md:flex items-center space-x-8">
                <a href="#" class="hover:text-gray-300 transition" data-section="home">Home</a>
                <a href="#" class="hover:text-gray-300 transition" data-section="products">Produtos</a>
                <a href="#" class="hover:text-gray-300 transition" data-section="collections">Coleções</a>
                <a href="#" class="hover:text-gray-300 transition" data-section="about">Sobre</a>
            </div>
            
            <div class="flex items-center space-x-6">
                <button id="search-toggle" class="hover:text-gray-300 transition text-black dark:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
                <div class="flex items-center space-x-4">
                    <button id="cart-toggle" class="text-black dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="absolute -top-2 -right-2 bg-white text-black text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">0</span>
                    </button>
                    <button id="account-toggle" class="hidden md:block text-black dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </button>
                </div>
                <button id="menu-toggle" class="md:hidden hover:text-gray-300 transition text-black dark:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div id="mobile-menu" class="fixed inset-0 bg-white dark:bg-black z-50 hidden">
    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-between items-center mb-8">
            <a href="/" class="flex items-center">
                <img src="https://i.postimg.cc/QCMM1Byn/WIDE-STYLE-SITE.png" alt="Wide Style Logo" class="h-8 dark:hidden">
                <img src="https://i.postimg.cc/Gm9sQYcb/WIDE-STYLE-SITE-DARK.png" alt="Wide Style Logo" class="h-8 hidden dark:block">
            </a>
            <button id="close-menu" class="text-black dark:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Search Bar -->
        <div class="mb-8">
            <form id="mobile-search-form" class="relative">
                <input type="text" id="mobile-search-input" placeholder="Buscar produtos..." class="w-full bg-gray-100 dark:bg-zinc-900 text-black dark:text-white py-3 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </form>
        </div>
        
        <!-- Navigation Links -->
        <nav class="space-y-4">
            <a href="/" class="block text-lg font-medium hover:text-purple-500 transition">Home</a>
            <a href="#products" class="block text-lg font-medium hover:text-purple-500 transition">Produtos</a>
            <a href="#about" class="block text-lg font-medium hover:text-purple-500 transition">Sobre</a>
            <a href="#contact" class="block text-lg font-medium hover:text-purple-500 transition">Contato</a>
        </nav>
    </div>
</div>

<!-- Search Bar (hidden by default) -->
<div id="search-bar" class="fixed top-0 left-0 right-0 bg-white/90 dark:bg-black/90 backdrop-blur-sm z-50 py-6 hidden">
    <div class="container mx-auto px-4">
        <div class="relative">
            <input type="text" placeholder="Buscar produtos..." class="w-full bg-gray-100 dark:bg-zinc-900 border-none py-3 pl-4 pr-10 text-black dark:text-white focus:outline-none rounded-lg">
            <button id="close-search" class="absolute right-3 top-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Mobile Bottom Navigation (visible on mobile only) -->
<div class="mobile-bottom-nav hidden sm:hidden">
    <div class="flex justify-around items-center h-16">
        <a href="#" class="flex flex-col items-center justify-center text-gray-800 dark:text-gray-400 hover:text-gray-600 dark:hover:text-white" data-section="home">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="text-xs mt-1">Home</span>
        </a>
        <a href="#" class="flex flex-col items-center justify-center text-gray-800 dark:text-gray-400 hover:text-gray-600 dark:hover:text-white" data-section="products">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            <span class="text-xs mt-1">Produtos</span>
        </a>
        <a href="#" class="flex flex-col items-center justify-center text-gray-800 dark:text-gray-400 hover:text-gray-600 dark:hover:text-white relative" id="mobile-cart-toggle">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span class="absolute -top-2 -right-2 bg-gray-200 dark:bg-white text-black text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">0</span>
            <span class="text-xs mt-1">Carrinho</span>
        </a>
        <a href="#" class="flex flex-col items-center justify-center text-gray-800 dark:text-gray-400 hover:text-gray-600 dark:hover:text-white" id="mobile-account-toggle">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="text-xs mt-1">Conta</span>
        </a>
    </div>
</div>


<!-- Main Content -->
<main>
   <section class="relative h-screen flex items-center">
    <div class="absolute inset-0 z-0">
        <!-- Light mode banner -->
        <picture class="dark:hidden">
            <source media="(max-width: 767px)" srcset="https://i.postimg.cc/vBn7MJr6/C-pia-de-C-pia-de-Red-Beige-Aesthetic-Blob-Faith-Hope-Love-Christian-Desktop-Wallpaper-Story.png">
            <img src="https://i.postimg.cc/KvkqjWmQ/modobranco-1.png" alt="Wide Style Collection" class="w-full h-full object-cover">
        </picture>
        
        <!-- Dark mode banner -->
        <picture class="hidden dark:block">
            <source media="(max-width: 767px)" srcset="https://i.postimg.cc/fb73nSSJ/C-pia-de-C-pia-de-Red-Beige-Aesthetic-Blob-Faith-Hope-Love-Christian-Desktop-Wallpaper-Story-1.png">
            <img src="https://i.postimg.cc/WzYFYRF8/C-pia-de-C-pia-de-Red-Beige-Aesthetic-Blob-Faith-Hope-Love-Christian-Desktop-Wallpaper.png" alt="Wide Style Collection" class="w-full h-full object-cover">
        </picture>
        
        <div class="absolute inset-0 bg-black/50"></div>
    </div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-xl">
            <h1 class="text-4xl md:text-6xl font-bold mb-4">Nova Coleção Jesus is King</h1>
            <p class="text-xl mb-8">Coleção Jesus is King: estilo urbano com significado espiritual. Peças exclusivas que unem fé e personalidade.</p>
            <div class="flex space-x-4">
                <button class="bg-white text-black px-8 py-3 font-medium hover:bg-gray-200 transition" data-section="products">COMPRAR AGORA</button>
                <button class="border border-white px-8 py-3 font-medium hover:bg-white hover:text-black transition" data-section="collections">VER COLEÇÃO</button>
            </div>
        </div>
    </div>
</section>
        <section class="py-16 bg-gray-100 dark:bg-zinc-950">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-8 text-center">Categorias em Destaque</h2>         
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 md:gap-8">
                <div class="relative aspect-[3/4] group overflow-hidden rounded-lg">
                    <img src="https://i.postimg.cc/85yjcBz0/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt.png" alt="Camisetas" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-black/30 group-hover:bg-black/50 transition-all duration-300"></div>
                    <div class="absolute inset-0 flex flex-col justify-end p-4 md:p-6">
                        <h3 class="text-xl md:text-2xl font-bold mb-1 md:mb-2">Camisetas</h3>
                        <p class="text-sm md:text-base mb-3 md:mb-4">Designs exclusivos para seu estilo único</p>
                        <button class="bg-white text-black w-full py-2 md:py-3 font-medium opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300" data-category="tshirts">EXPLORAR</button>
                    </div>
                </div>
                <div class="relative aspect-[3/4] group overflow-hidden rounded-lg">
                    <img src="https://i.postimg.cc/4xCnY3Xs/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-1.png" alt="Moletons" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-black/30 group-hover:bg-black/50 transition-all duration-300"></div>
                    <div class="absolute inset-0 flex flex-col justify-end p-4 md:p-6">
                        <h3 class="text-xl md:text-2xl font-bold mb-1 md:mb-2">Moletons</h3>
                        <p class="text-sm md:text-base mb-3 md:mb-4">Conforto e estilo para os dias mais frios</p>
                        <button class="bg-white text-black w-full py-2 md:py-3 font-medium opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300" data-category="hoodies">EXPLORAR</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
        <section id="products" class="py-16 bg-white dark:bg-black">
            <div class="relative">
                <div class="container mx-auto px-4">
                    <h2 class="text-3xl font-bold mb-4 text-center">Nossos Produtos</h2>
                    <p class="text-gray-600 dark:text-gray-400 text-center mb-8">Estilo urbano para todos os momentos</p>
                    
                    <div class="sticky top-16 bg-white dark:bg-black z-10 -mx-4 px-4 py-4">
                        <div class="category-tabs flex overflow-x-auto space-x-2 pb-2">
                            <button class="category-filter whitespace-nowrap px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-full">Todos</button>
                            <button class="category-filter whitespace-nowrap px-4 py-2 bg-gray-200 dark:bg-zinc-900 hover:bg-gray-300 dark:hover:bg-zinc-800 rounded-full">Camisetas</button>
                            <button class="category-filter whitespace-nowrap px-4 py-2 bg-gray-200 dark:bg-zinc-900 hover:bg-gray-300 dark:hover:bg-zinc-800 rounded-full">Moletons</button>
                            <button class="category-filter whitespace-nowrap px-4 py-2 bg-gray-200 dark:bg-zinc-900 hover:bg-gray-300 dark:hover:bg-zinc-800 rounded-full">Novidades</button>
                            <button class="category-filter whitespace-nowrap px-4 py-2 bg-gray-200 dark:bg-zinc-900 hover:bg-gray-300 dark:hover:bg-zinc-800 rounded-full">Mais Vendidos</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container mx-auto px-4">
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6 mt-8">
                <!-- Product 1 -->
                <div class="product-card group">
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg product-image-container">
                        <img src="https://i.postimg.cc/6QM9f6x7/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-2.png" alt="Divine Steps Oversized" class="w-full h-full object-cover product-image-front">
                        <img src="https://i.postimg.cc/zBM84dB8/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-3.png" alt="Divine Steps Oversized - Back" class="w-full h-full object-cover product-image-back">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-favorites" data-product="1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-cart" data-product="1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 quick-view" data-product="1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Camisas</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">Divine Steps Oversized</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ 199,90</p>
                    </div>
                </div>
                
                <!-- Product 2 -->
                <div class="product-card group">
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg product-image-container">
                        <img src="https://i.postimg.cc/dtcL11sv/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-4.png" alt="São Miguel Arcanjo" class="w-full h-full object-cover product-image-front">
                        <img src="https://i.postimg.cc/CK5KtnNC/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-5.png" alt="São Miguel Arcanjo - Back" class="w-full h-full object-cover product-image-back">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-favorites" data-product="2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-cart" data-product="2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 quick-view" data-product="2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Camisas</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">São Miguel Arcanjo</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ 199,90</p>
                    </div>
                </div>
                
                <!-- Product 3 -->
                <div class="product-card group">
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg product-image-container">
                        <img src="https://i.postimg.cc/gc82fWv7/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-6.png" alt="Crown of Belief" class="w-full h-full object-cover product-image-front">
                        <img src="https://i.postimg.cc/C5BB5GQG/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-4.png" alt="Crown of Belief - Back" class="w-full h-full object-cover product-image-back">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-favorites" data-product="3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-cart" data-product="3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 quick-view" data-product="3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Camisas</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">Crown of Belief</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ 199,90</p>
                    </div>
                </div>
                
                <!-- Product 4 -->
                <div class="product-card group">
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg product-image-container">
                        <img src="https://i.postimg.cc/85WTQRrv/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-8.png" alt="Rebel Art" class="w-full h-full object-cover product-image-front">
                        <img src="https://i.postimg.cc/kGv9D7sX/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-9.png" alt="Rebel Art - Back" class="w-full h-full object-cover product-image-back">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-favorites" data-product="4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-cart" data-product="4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 quick-view" data-product="4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Moletons</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">Rebel Art</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ 249,90</p>
                    </div>
                </div>
                
                <!-- Product 5 -->
                <div class="product-card group">
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg product-image-container">
                        <img src="https://i.postimg.cc/6QM9f6x7/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-2.png" alt="in love we trust Tee" class="w-full h-full object-cover product-image-front">
                        <img src="https://i.postimg.cc/9fwhZdbk/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-10.png" alt="in love we trust Tee - Back" class="w-full h-full object-cover product-image-back">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-favorites" data-product="5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-cart" data-product="5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 quick-view" data-product="5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Camisas</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">in love we trust Tee</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ 169,90</p>
                    </div>
                </div>
                
                <!-- Product 6 -->
                <div class="product-card group">
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg product-image-container">
                        <img src="https://i.postimg.cc/gc82fWv7/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-6.png" alt="Godly Expression" class="w-full h-full object-cover product-image-front">
                        <img src="https://i.postimg.cc/0jbmmRbJ/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-2.png" alt="Godly Expression - Back" class="w-full h-full object-cover product-image-back">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-favorites" data-product="6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-cart" data-product="6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 quick-view" data-product="6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Camisas</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">Godly Expression</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ 229,90</p>
                    </div>
                </div>
                
                <!-- Product 7 -->
                <div class="product-card group">
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg product-image-container">
                        <img src="https://i.postimg.cc/6QM9f6x7/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-2.png" alt="Natus Vincere" class="w-full h-full object-cover product-image-front">
                        <img src="https://i.postimg.cc/8zjQgR2C/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-12.png" alt="Natus Vincere - Back" class="w-full h-full object-cover product-image-back">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-favorites" data-product="7">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-cart" data-product="7">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 quick-view" data-product="7">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Camisas</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">Natus Vincere</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ 189,90</p>
                    </div>
                </div>
                
                <!-- Product 8 -->
                <div class="product-card group">
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg product-image-container">
                        <img src="https://i.postimg.cc/pr24GzQR/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-13.png" alt="true love" class="w-full h-full object-cover product-image-front">
                        <img src="https://i.postimg.cc/qqjY9qQn/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-14.png" alt="true love - Back" class="w-full h-full object-cover product-image-back">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-favorites" data-product="8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-cart" data-product="8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 quick-view" data-product="8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Moletons</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">true love</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ 219,90</p>
                    </div>
                </div>
                
              <div class="product-card group">
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg product-image-container">
                        <img src="https://i.postimg.cc/VLZpgSJP/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-15.png" alt="Rogue Script" class="w-full h-full object-cover product-image-front">
                        <img src="https://i.postimg.cc/cLVjqV7D/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-16.png" alt="Rogue Script - Back" class="w-full h-full object-cover product-image-back">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-favorites" data-product="9">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-cart" data-product="9">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 quick-view" data-product="9">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Moletons</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">Rogue Script</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ 219,90</p>
                    </div>
                </div>

                <div class="product-card group">
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg product-image-container">
                        <img src="https://i.postimg.cc/6QM9f6x7/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-2.png" alt="God in my heart" class="w-full h-full object-cover product-image-front">
                        <img src="https://i.postimg.cc/7hW3QPpV/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-3.png" alt="God in my heart - Back" class="w-full h-full object-cover product-image-back">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-favorites" data-product="10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-cart" data-product="10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 quick-view" data-product="10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Camisas</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">God in my heart</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ 189,90</p>
                    </div>
                </div>

                <div class="product-card group">
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg product-image-container">
                        <img src="https://i.postimg.cc/dtcL11sv/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-4.png" alt="jesus never abandons Tee" class="w-full h-full object-cover product-image-front">
                        <img src="https://i.postimg.cc/nh87sWZC/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt.png" alt="jesus never abandons Tee - Back" class="w-full h-full object-cover product-image-back">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-favorites" data-product="11">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-cart" data-product="11">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 quick-view" data-product="11">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Moletons</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">jesus never abandons Tee</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ 219,90</p>
                    </div>
                </div>

                <div class="product-card group">
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg product-image-container">
                        <img src="https://i.postimg.cc/85WTQRrv/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-8.png" alt="Saved by Grace" class="w-full h-full object-cover product-image-front">
                        <img src="https://i.postimg.cc/Kv3B8266/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-1.png" alt="Saved by Grace - Back" class="w-full h-full object-cover product-image-back">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-favorites" data-product="12">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 add-to-cart" data-product="12">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 quick-view" data-product="12">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Moletons</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">Saved by Grace</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ 219,90</p>
                    </div>
                </div>
                <!-- Products 9-16 would follow the same pattern as above -->
                
                <!-- Load More Button -->
                <!-- <button id="load-more" class="border border-white px-8 py-3 font-medium hover:bg-white hover:text-black transition rounded-lg">CARREGAR MAIS</button> -->
            </div>
            
            <!-- Floating Add to Cart Button (Mobile Only) -->
            <button class="floating-add-btn bg-white text-black w-14 h-14 rounded-full flex items-center justify-center shadow-lg md:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </button>
        </div>
    </section>
    
    <!-- Featured Collection -->
    <section id="collections" class="py-16 bg-gray-100 dark:bg-zinc-950 relative min-h-[600px] md:min-h-[800px]">
        <div class="absolute inset-0 z-0 bg-black opacity-80"></div>
        <div class="absolute inset-0 z-0">
            <img src="https://i.postimg.cc/k5bpQzRz/Jesus-is-God-1140-x-720-px-1.png" alt="Collection Background Desktop" class="hidden md:block w-full h-full object-cover opacity-30 md:object-center">
            <img src="Jesus is God.png" alt="Collection Background Mobile" class="md:hidden w-full h-full object-cover opacity-30 object-[80%_center]">
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="flex flex-col md:flex-row items-center justify-between h-full">
                <div class="w-full md:w-1/2 mb-8 md:mb-0 md:max-w-xl">
                    <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-4">Coleção Jesus is King</h2>
                    <p class="text-gray-400 mb-6 text-lg md:text-xl">A coleção "Jesus is King" mistura estilo urbano com conforto, trazendo camisas oversized e moletons que refletem a fé de forma autêntica e cheia de atitude.</p>
                    <button class="bg-white text-black px-8 py-3 font-medium hover:bg-gray-200 transition rounded-lg text-lg" data-section="collection-details">EXPLORAR COLEÇÃO</button>
                </div>
            </div>
        </div>
    </section>
    
    <!-- About Section -->
    <!-- <section id="about" class="py-16 bg-black">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center">
                <div class="w-full md:w-1/2 mb-8 md:mb-0">
                    <img src="img/about.jpg" alt="About Wide Style" class="w-full h-auto rounded-lg">
                </div>
                
                <div class="w-full md:w-1/2 md:pl-12">
                    <h2 class="text-3xl font-bold mb-4">Nossa História</h2>
                    <p class="text-gray-400 mb-6">Fundada em 2018, a Wide Style nasceu da paixão por moda urbana e design autêntico. Nossa missão é criar peças que combinam estilo, conforto e atitude, permitindo que cada pessoa expresse sua individualidade através da moda.</p>
                    <p class="text-gray-400 mb-6">Trabalhamos com materiais de alta qualidade e processos sustentáveis, garantindo que cada peça não apenas pareça incrível, mas também seja durável e responsável.</p>
                    <button class="border border-white px-8 py-3 font-medium hover:bg-white hover:text-black transition rounded-lg" data-section="about-details">SAIBA MAIS</button>
                </div>
            </div>
        </div>
    </section> -->
    
    <!-- Video Section -->
    <section class="py-8 bg-white dark:bg-black">
        <div class="container mx-auto px-2">
            <div class="aspect-video w-full max-w-[95vw] lg:max-w-[1280px] mx-auto rounded-lg overflow-hidden relative m-2">
                <video 
                    id="mainVideo"
                    class="w-full h-full object-cover"
                    width="1280"
                    height="720"
                    autoplay 
                    loop 
                    muted 
                    playsinline
                >
                    <source src="Design sem nome (3).mp4" type="video/mp4">
                    Seu navegador não suporta vídeos HTML5.
                </video>
                <button id="playPauseBtn" class="absolute bottom-4 right-4 w-12 h-12 bg-black bg-opacity-50 rounded-full flex items-center justify-center hover:bg-opacity-75 transition-all">
                    <div class="absolute w-11 h-11 border border-white rounded-full"></div>
                    <span id="playIcon" class="hidden text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653Z" />
                        </svg>
                    </span>
                    <span id="pauseIcon" class="text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                        </svg>
                    </span>
                </button>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<footer class="bg-gray-100 dark:bg-zinc-950 text-black dark:text-white">
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div>
                <img src="https://i.postimg.cc/tgYF14JZ/WIDE-STYLE-SITE-BRANCO.png" alt="WIDE STYLE" class="h-10 mb-4 dark:hidden">
                <img src="https://i.postimg.cc/QCMM1Byn/WIDE-STYLE-SITE.png" alt="WIDE STYLE" class="h-10 mb-4 hidden dark:block">
                <p class="text-gray-400 text-sm">Moda urbana com atitude e estilo para quem busca autenticidade.</p>
                <div class="flex space-x-4 mt-6">
                    <a href="#" class="hover:text-gray-300 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                        </svg>
                    </a>
                    <a href="#" class="hover:text-gray-300 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                    <a href="#" class="hover:text-gray-300 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                        </svg>
                    </a>
                    <a href="#" class="hover:text-gray-300 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <div>
                <h3 class="font-bold mb-4 text-sm">NAVEGAÇÃO</h3>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li><a href="#" class="hover:text-white transition" data-section="home">Home</a></li>
                    <li><a href="#" class="hover:text-white transition" data-section="products">Produtos</a></li>
                    <li><a href="#" class="hover:text-white transition" data-section="collections">Coleções</a></li>
                    <li><a href="#" class="hover:text-white transition" data-section="about">Sobre</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="font-bold mb-4 text-sm">AJUDA</h3>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li><a href="#" class="hover:text-white transition">FAQ</a></li>
                    <li><a href="#" class="hover:text-white transition">Envio e Entrega</a></li>
                    <li><a href="#" class="hover:text-white transition">Trocas e Devoluções</a></li>
                    <li><a href="#" class="hover:text-white transition">Política de Privacidade</a></li>
                    <li><a href="#" class="hover:text-white transition">Termos e Condições</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="font-bold mb-4 text-sm">CONTATO</h3>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li>contato@widestyle.com</li>
                    <li>+55 (85) 98479-7128</li>
                    <li>Amanari - Maranguape, CE</li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-300 dark:border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 md:mb-0">© 2025 Wide Style. Todos os direitos reservados.</p>
            <div class="flex space-x-4">
                <img src="https://www.svgrepo.com/show/508730/visa-classic.svg" alt="Visa" class="h-6">
                <img src="https://www.svgrepo.com/show/508703/mastercard.svg" alt="Mastercard" class="h-6">
                <img src="https://www.svgrepo.com/show/508403/amex.svg" alt="American Express" class="h-6">
                <img src="https://www.svgrepo.com/show/500416/pix.svg" alt="Pix" class="h-6 bg-white border-5 border-black">
            </div>
        </div>
    </div>
</footer>

<!-- Full Screen Modals -->

<!-- Product Detail Modal -->
<div id="product-detail-modal" class="full-screen-modal bg-white dark:bg-black">
    <div class="container mx-auto px-4 py-20">
        <button class="close-modal absolute top-6 right-6 z-10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
            <!-- Product Images -->
            <div class="w-full lg:w-3/5">
                <div class="flex flex-col gap-4">
                    <!-- Main Image -->
                    <div class="aspect-square bg-zinc-900 rounded-lg overflow-hidden">
                        <img src="img/product-1.jpg" alt="Product" class="w-full h-full object-cover rounded-lg" id="main-product-image">
                    </div>
                    
                    <!-- Thumbnails -->
                    <div class="grid grid-cols-4 gap-2">
                        <div class="aspect-square bg-zinc-900 cursor-pointer hover:opacity-80 transition product-thumbnail rounded-lg" data-image="img/product-1.jpg">
                            <img src="img/product-1.jpg" alt="Product - Front View" class="w-full h-full object-cover rounded-lg">
                        </div>
                        <div class="aspect-square bg-zinc-900 cursor-pointer hover:opacity-80 transition product-thumbnail rounded-lg" data-image="img/product-1-back.jpg">
                            <img src="img/product-1-back.jpg" alt="Product - Back View" class="w-full h-full object-cover rounded-lg">
                        </div>
                        <!-- <div class="aspect-square bg-zinc-900 cursor-pointer hover:opacity-80 transition product-thumbnail rounded-lg" data-image="img/product-1-detail.jpg">
                            <img src="img/product-1-detail.jpg" alt="Product - Detail" class="w-full h-full object-cover rounded-lg">
                        </div>
                        <div class="aspect-square bg-zinc-900 cursor-pointer hover:opacity-80 transition product-thumbnail rounded-lg" data-image="img/product-1-model.jpg">
                            <img src="img/product-1-model.jpg" alt="Product - On Model" class="w-full h-full object-cover rounded-lg">
                        </div> -->
                    </div>
                </div>
            </div>
            
            <!-- Product Info -->
            <div class="w-full lg:w-2/5">
                <h2 id="product-title" class="text-2xl md:text-3xl font-bold mb-4"></h2>
                <p id="product-price" class="text-xl md:text-2xl text-white mb-6"></p>
                <p id="product-description" class="text-gray-400 mb-8"></p>
                
                <!-- Size Selection -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Tamanho</h3>
                    <div id="product-sizes" class="flex flex-wrap gap-2">
                        <button class="size-option border border-gray-600 px-4 py-2 rounded hover:border-white transition">P</button>
                        <button class="size-option border border-gray-600 px-4 py-2 rounded hover:border-white transition">M</button>
                        <button class="size-option border border-gray-600 px-4 py-2 rounded hover:border-white transition">G</button>
                        <button class="size-option border border-gray-600 px-4 py-2 rounded hover:border-white transition">GG</button>
                    </div>
                </div>
                
                <!-- Color Selection -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Cor</h3>
                    <div id="product-colors" class="flex flex-wrap gap-2">
                        <!-- As cores serão reorganizadas dinamicamente pelo JavaScript -->
                    </div>
                </div>
                
                <!-- Quantity -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Quantidade</h3>
                    <div class="flex items-center gap-4">
                        <button id="decrease-quantity" class="w-10 h-10 flex items-center justify-center border border-gray-600 rounded hover:border-white transition">-</button>
                        <span id="product-quantity" class="text-xl">1</span>
                        <button id="increase-quantity" class="w-10 h-10 flex items-center justify-center border border-gray-600 rounded hover:border-white transition">+</button>
                    </div>
                </div>
                
                <!-- Add to Cart Button -->
                <button id="modal-add-to-cart" class="w-full bg-white text-black py-4 rounded-lg font-semibold hover:bg-gray-100 transition">
                    Adicionar ao Carrinho
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cart Modal -->
<div id="cart-modal" class="full-screen-modal bg-white dark:bg-black">
    <div class="container mx-auto px-4 py-20">
        <button class="close-modal absolute top-6 right-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <h1 class="text-3xl font-bold mb-8">Seu Carrinho</h1>
        
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Cart Items -->
            <div class="w-full lg:w-2/3">
                <div class="cart-items space-y-6">
                    <!-- Empty cart message (shown by default, hidden when items are added) -->
                    <p class="text-center py-8 text-gray-400" id="empty-cart-message">Seu carrinho está vazio.</p>
                    
                    <!-- Cart items will be dynamically added here -->
                </div>
            </div>
            
            <!-- Cart Summary -->
            <div class="w-full lg:w-1/3">
                <div class="bg-gray-100 dark:bg-zinc-950 p-6 sticky top-20 rounded-lg">
                    <h2 class="text-xl font-bold mb-6">Resumo do Pedido</h2>
                    
                    <div class="border-b border-gray-800 pb-4 mb-4">
                        <div class="flex justify-between mb-2">
                            <span>Subtotal</span>
                            <span id="cart-subtotal">R$ 0,00</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span>Frete</span>
                            <span id="cart-shipping">Calculado no checkout</span>
                        </div>
                    </div>
                    
                    <div class="border-b border-gray-800 pb-4 mb-6">
                        <div class="flex justify-between text-xl font-bold">
                            <span>Total</span>
                            <span id="cart-total">R$ 0,00</span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="coupon" class="block text-sm mb-1">Cupom de Desconto</label>
                        <div class="flex">
                            <input type="text" id="coupon" class="flex-1 bg-black border border-gray-300 p-3 text-yellow-500 focus:outline-none focus:border-white rounded-l-md">
                            <button class="bg-white text-black px-4 hover:bg-gray-200 transition rounded-r-md" id="apply-coupon">Aplicar</button>
                        </div>
                    </div>
                    
                    <button class="w-full bg-black dark:bg-white text-white dark:text-black py-3 font-medium hover:bg-gray-800 dark:hover:bg-gray-200 transition rounded-md" id="proceed-to-checkout">FINALIZAR COMPRA</button>
                    
                    <button class="w-full border border-black dark:border-white py-3 mt-4 font-medium hover:bg-white hover:text-black transition rounded-md close-modal">CONTINUAR COMPRANDO</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div id="checkout-modal" class="full-screen-modal bg-white dark:bg-black">
    <div class="container mx-auto px-4 py-20">
        <button class="close-modal absolute top-6 right-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <h1 class="text-3xl font-bold mb-8 text-center">Checkout</h1>
        
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Checkout Form -->
            <div class="w-full lg:w-2/3">
                <!-- Progress Steps -->
                <div class="flex justify-between mb-12">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center font-medium dark:bg-white dark:text-black">1</div>
                        <span class="mt-2 text-sm">Informações</span>
                    </div>
                    <div class="flex-1 flex items-center mx-4">
                        <div class="h-0.5 w-full bg-gray-300 dark:bg-gray-800"></div>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-700 flex items-center justify-center font-medium dark:bg-gray-800 dark:text-white">2</div>
                        <span class="mt-2 text-sm">Envio</span>
                    </div>
                    <div class="flex-1 flex items-center mx-4">
                        <div class="h-0.5 w-full bg-gray-300 dark:bg-gray-800"></div>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-700 flex items-center justify-center font-medium dark:bg-gray-800 dark:text-white">3</div>
                        <span class="mt-2 text-sm">Pagamento</span>
                    </div>
                </div>
                
                <!-- Customer Information -->
                <div class="mb-12 checkout-section" id="info-section">
                    <h2 class="text-xl font-bold mb-6">Informações de Contato</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm mb-1">Email</label>
                            <input type="email" id="email" class="w-full bg-black border border-gray-300 p-3 text-white focus:outline-none focus:border-white rounded-md" required>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="newsletter" class="mr-3">
                            <label for="newsletter">Quero receber novidades e promoções por email</label>
                        </div>
                    </div>
                    
                    <h2 class="text-xl font-bold mt-8 mb-6">Endereço de Entrega</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="first-name" class="block text-sm mb-1">Nome</label>
                            <input type="text" id="first-name" class="w-full bg-black border border-gray-300 p-3 text-white focus:outline-none focus:border-white rounded-md" required>
                        </div>
                        <div>
                            <label for="last-name" class="block text-sm mb-1">Sobrenome</label>
                            <input type="text" id="last-name" class="w-full bg-black border border-gray-300 p-3 text-white focus:outline-none focus:border-white rounded-md" required>
                        </div>
                        <div class="md:col-span-2">
                            <label for="address" class="block text-sm mb-1">Endereço</label>
                            <input type="text" id="address" class="w-full bg-black border border-gray-300 p-3 text-white focus:outline-none focus:border-white rounded-md" required>
                        </div>
                        <div>
                            <label for="city" class="block text-sm mb-1">Cidade</label>
                            <input type="text" id="city" class="w-full bg-black border border-gray-300 p-3 text-white focus:outline-none focus:border-white rounded-md" required>
                        </div>
                        <div>
                            <label for="state" class="block text-sm mb-1">Estado</label>
                            <select id="state" class="w-full bg-black border border-gray-300 p-3 text-white focus:outline-none focus:border-white rounded-md" required>
                                <option value="">Selecione...</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>
                        <div>
                            <label for="zip" class="block text-sm mb-1">CEP</label>
                            <input type="text" id="zip" class="w-full bg-black border border-gray-300 p-3 text-white focus:outline-none focus:border-white rounded-md" required>
                        </div>
                        <div>
                            <label for="phone" class="block text-sm mb-1">Telefone</label>
                            <input type="tel" id="phone" class="w-full bg-black border border-gray-300 p-3 text-white focus:outline-none focus:border-white rounded-md" required>
                        </div>
                    </div>
                    
                    <div class="flex justify-between mt-8">
                        <button id="back-to-cart" class="flex items-center text-black dark:text-gray-400 hover:text-gray-700 dark:hover:text-white transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Voltar ao carrinho
                        </button>
                        <button id="continue-to-shipping" class="bg-black dark:bg-white text-white dark:text-black px-6 py-3 font-medium hover:bg-gray-800 dark:hover:bg-gray-200 transition rounded-md">
                            Continuar para envio
                        </button>
                    </div>
                </div>
                
                <!-- Shipping Method (initially hidden) -->
                <div class="mb-12 checkout-section hidden" id="shipping-section">
                    <h2 class="text-xl font-bold mb-6">Método de Envio</h2>
                    
                    <div class="space-y-4">
                        <div class="border border-gray-300 dark:border-gray-800 p-4 rounded-md">
                            <label class="flex items-center">
                                <input type="radio" name="shipping" class="mr-3" checked>
                                <div class="flex-1">
                                    <span class="block">Entrega Padrão</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">3-5 dias úteis</span>
                                </div>
                                <span>Grátis</span>
                            </label>
                        </div>
                        
                        <div class="border border-gray-300 dark:border-gray-800 p-4 rounded-md">
                            <label class="flex items-center">
                                <input type="radio" name="shipping" class="mr-3">
                                <div class="flex-1">
                                    <span class="block">Entrega Expressa</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">1-2 dias úteis</span>
                                </div>
                                <span>R$ 29,90</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-between mt-8">
                        <button id="back-to-info" class="flex items-center text-black dark:text-gray-400 hover:text-gray-700 dark:hover:text-white transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Voltar às informações
                        </button>
                        <button id="continue-to-payment" class="bg-black dark:bg-white text-white dark:text-black px-6 py-3 font-medium hover:bg-gray-800 dark:hover:bg-gray-200 transition rounded-md">
                            Continuar para pagamento
                        </button>
                    </div>
                </div>
                
                <!-- Payment Method (initially hidden) -->
                <div class="mb-12 checkout-section hidden" id="payment-section">
                    <h2 class="text-xl font-bold mb-6">Método de Pagamento</h2>
                    
                    <div class="space-y-6">
                        <div class="border border-gray-300 dark:border-gray-800 p-4 rounded-md">
                            <div class="flex items-center">
                                <input type="radio" id="credit-card-payment" name="payment-method" class="mr-3" checked>
                                <label for="credit-card-payment" class="flex-1">Cartão de Crédito</label>
                                <div class="flex space-x-2">
                                    <div class="h-6 w-10 bg-white p-1 rounded-sm"></div>
                                    <div class="h-6 w-10 bg-white p-1 rounded-sm"></div>
                                    <div class="h-6 w-10 bg-white p-1 rounded-sm"></div>
                                </div>
                            </div>
                            <div class="mt-4 space-y-3 payment-details">
                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <label class="block text-sm mb-1">Número do Cartão</label>
                                        <input type="text" class="w-full bg-white border border-gray-300 p-3 text-black focus:outline-none focus:border-black rounded-md" placeholder="0000 0000 0000 0000">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm mb-1">Validade</label>
                                        <input type="text" class="w-full bg-white border border-gray-300 p-3 text-black focus:outline-none focus:border-black rounded-md" placeholder="MM/AA">
                                    </div>
                                    <div>
                                        <label class="block text-sm mb-1">CVV</label>
                                        <input type="text" class="w-full bg-white border border-gray-300 p-3 text-black focus:outline-none focus:border-black rounded-md" placeholder="123">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm mb-1">Nome no Cartão</label>
                                    <input type="text" class="w-full bg-white border border-gray-300 p-3 text-black focus:outline-none focus:border-black rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm mb-1">Parcelas</label>
                                    <select class="w-full bg-white border border-gray-300 p-3 text-black focus:outline-none focus:border-black rounded-md" id="installments">
                                        <option>1x sem juros</option>
                                        <option>2x sem juros</option>
                                        <option>3x sem juros</option>
                                        <option>4x sem juros</option>
                                        <option>5x sem juros</option>
                                        <option>6x sem juros</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border border-gray-300 dark:border-gray-800 p-4 rounded-md">
                            <div class="flex items-center">
                                <input type="radio" id="pix-payment" name="payment-method" class="mr-3">
                                <label for="pix-payment" class="flex-1">PIX</label>
                                <div class="h-6 w-10 bg-white p-1 rounded-sm"></div>
                            </div>
                            <div class="mt-4 payment-details hidden">
                                <p class="text-gray-400 mb-4">Ao finalizar sua compra, você receberá um QR Code para pagamento.</p>
                            </div>
                        </div>
                        
                        <div class="border border-gray-300 dark:border-gray-800 p-4 rounded-md">
                            <div class="flex items-center">
                                <input type="radio" id="boleto-payment" name="payment-method" class="mr-3">
                                <label for="boleto-payment" class="flex-1">Boleto Bancário</label>
                                <div class="h-6 w-10 bg-white p-1 rounded-sm"></div>
                            </div>
                            <div class="mt-4 payment-details hidden">
                                <p class="text-gray-400">O boleto será gerado após a finalização da compra e tem vencimento em 3 dias úteis.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-between mt-8">
                        <button id="back-to-shipping" class="flex items-center text-black dark:text-gray-400 hover:text-gray-700 dark:hover:text-white transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Voltar ao envio
                        </button>
                        <button id="place-order" class="bg-black dark:bg-white text-white dark:text-black px-6 py-3 font-medium hover:bg-gray-800 dark:hover:bg-gray-200 transition rounded-md">
                            Finalizar Pedido
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="w-full lg:w-1/3">
                <div class="bg-white dark:bg-zinc-950 p-6 sticky top-20 rounded-lg border border-gray-300 dark:border-gray-800">
                    <h2 class="text-xl font-bold mb-6">Resumo do Pedido</h2>
                    
                    <div class="space-y-4 mb-6" id="checkout-items">
                        <!-- Checkout items will be dynamically added here -->
                    </div>
                    
                    <div class="border-t border-gray-300 dark:border-gray-800 pt-4 mb-4">
                        <div class="flex justify-between mb-2">
                            <span>Subtotal</span>
                            <span id="checkout-subtotal">R$ 0,00</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span>Frete</span>
                            <span id="checkout-shipping">Grátis</span>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-300 dark:border-gray-800 pt-4 mb-6">
                        <div class="flex justify-between text-xl font-bold">
                            <span>Total</span>
                            <span id="checkout-total">R$ 0,00</span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="checkout-coupon" class="block text-sm mb-1">Cupom de Desconto</label>
                        <div class="flex">
                            <input type="text" id="checkout-coupon" class="flex-1 bg-black border border-gray-300 p-3 text-black focus:outline-none focus:border-black rounded-l-md">
                            <button class="bg-white text-black px-4 hover:bg-gray-200 transition rounded-r-md" id="apply-checkout-coupon">Aplicar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Confirmation Modal -->
<div id="order-confirmation-modal" class="full-screen-modal bg-white dark:bg-black">
    <div class="container mx-auto px-4 py-20">
        <div class="max-w-2xl mx-auto text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 mx-auto mb-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            
            <h1 class="text-3xl font-bold mb-4">Pedido Realizado com Sucesso!</h1>
            <p class="text-xl mb-8">Obrigado por comprar na Wide Style.</p>
            
            <div class="bg-gray-100 dark:bg-zinc-950 p-6 mb-8 text-left rounded-lg">
                <div class="flex justify-between mb-4">
                    <span>Número do Pedido:</span>
                    <span id="order-number">ORD123456789</span>
                </div>
                <div class="flex justify-between mb-4">
                    <span>Data:</span>
                    <span id="order-date">12/04/2025</span>
                </div>
                <div class="flex justify-between mb-4">
                    <span>Email:</span>
                    <span id="order-email">cliente@gmail.com</span>
                </div>
                <div class="flex justify-between">
                    <span>Total:</span>
                    <span id="order-total">R$ 299,80</span>
                </div>
            </div>
            
            <p class="mb-8">Você receberá um email com os detalhes do seu pedido em breve.</p>
            
            <button class="bg-white text-black px-8 py-3 font-medium hover:bg-gray-200 transition rounded-md" id="continue-shopping">CONTINUAR COMPRANDO</button>
        </div>
    </div>
</div>

<!-- Account Modal -->
<div id="account-modal" class="full-screen-modal bg-white dark:bg-black">
    <div class="container mx-auto px-4 py-20">
        <button class="close-modal absolute top-6 right-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <div class="max-w-md mx-auto">
            <div class="flex justify-center mb-8">
                <img src="https://i.postimg.cc/QCMM1Byn/WIDE-STYLE-SITE.png" alt="WIDE STYLE" class="h-12 dark:hidden">
                <img src="https://i.postimg.cc/QCMM1Byn/WIDE-STYLE-SITE.png" alt="WIDE STYLE" class="h-12 hidden dark:block">
            </div>
            
            <div class="bg-gray-100 dark:bg-zinc-950 p-8 rounded-lg">
                <div class="flex border-b border-gray-800 mb-6">
                    <button id="login-tab" class="flex-1 py-3 font-medium text-center border-b-2 border-white">Entrar</button>
                    <button id="register-tab" class="flex-1 py-3 font-medium text-center text-gray-400">Registrar</button>
                </div>
                
                <!-- Login Form -->
                <form id="login-form" class="space-y-4">
                    <div>
                        <label for="login-email" class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" id="login-email" name="email" required class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-2 focus:outline-none focus:border-white">
                    </div>
                    <div>
                        <label for="login-password" class="block text-sm font-medium mb-1">Senha</label>
                        <div class="relative">
                            <input type="password" id="login-password" name="password" required class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:border-white">
                            <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white focus:outline-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 show-password" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hide-password hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <div>
                            <input type="checkbox" id="remember-me" class="rounded text-blue-500 focus:ring-blue-500">
                            <label for="remember-me" class="ml-2 text-sm text-black dark:text-white">Lembrar de mim</label>
                        </div>
                        <a href="#" id="forgot-password-link" class="text-sm text-blue-500 hover:text-blue-600 transition">Esqueceu a senha?</a>
                    </div>
                    <button type="submit" class="w-full bg-white text-black py-3 font-medium hover:bg-gray-200 transition rounded-md">Entrar</button>
                </form>
                
                <!-- Register Form (initially hidden) -->
                <form id="register-form" class="space-y-4 hidden">
                    <div>
                        <label for="register-name" class="block text-sm font-medium mb-1">Nome</label>
                        <input type="text" id="register-name" name="name" required class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-2 focus:outline-none focus:border-white">
                    </div>
                    <div>
                        <label for="register-email" class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" id="register-email" name="email" required class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-2 focus:outline-none focus:border-white">
                    </div>
                    <div>
                        <label for="register-password" class="block text-sm font-medium mb-1">Senha</label>
                        <div class="relative">
                            <input type="password" id="register-password" name="password" required class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-2 focus:outline-none focus:border-white">
                            <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 show-password" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hide-password hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="register-confirm-password" class="block text-sm font-medium mb-1">Confirmar Senha</label>
                        <div class="relative">
                            <input type="password" id="register-confirm-password" name="confirm-password" required class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-2 focus:outline-none focus:border-white">
                            <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 show-password" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hide-password hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="terms" class="mr-2" required>
                        <label for="terms" class="text-sm">Eu concordo com os <a href="#" class="text-gray-400 hover:text-white">Termos e Condições</a></label>
                    </div>
                    <button type="submit" class="w-full bg-white text-black py-3 font-medium hover:bg-gray-200 transition rounded-md">Registrar</button>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-400">Ou continue com</p>
                    <div class="flex justify-center space-x-4 mt-4">
                        <button class="w-10 h-10 rounded-full bg-zinc-900 flex items-center justify-center hover:bg-zinc-800 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                        </button>
                        <button class="w-10 h-10 rounded-full bg-zinc-900 flex items-center justify-center hover:bg-zinc-800 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Screen -->
<div id="loading-screen" class="fixed inset-0 bg-white/90 dark:bg-black/90 z-[200] flex items-center justify-center hidden">
    <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-white mb-4"></div>
        <p class="text-xl">Carregando...</p>
    </div>
</div>

<!-- User Profile Modal -->
<div id="profile-modal" class="full-screen-modal bg-white dark:bg-black">
    <div class="container mx-auto px-4 py-20">
        <button class="close-modal absolute top-6 right-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <div class="max-w-xl mx-auto">
            <div class="flex justify-center mb-8">
                <img src="https://i.postimg.cc/QCMM1Byn/WIDE-STYLE-SITE.png" alt="WIDE STYLE" class="h-12 dark:hidden">
                <img src="https://i.postimg.cc/QCMM1Byn/WIDE-STYLE-SITE.png" alt="WIDE STYLE" class="h-12 hidden dark:block">
            </div>
            
            <div class="bg-gray-100 dark:bg-zinc-950 p-8 rounded-lg">
                <div class="flex items-center mb-8">
                    <div class="w-16 h-16 bg-gray-800 rounded-full flex items-center justify-center mr-4">
                        <span class="text-2xl font-bold user-initial text-white">U</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold user-name text-black dark:text-white">Nome do Usuário</h2>
                        <p class="text-gray-400 user-email">usuario@exemplo.com</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <a href="#" class="flex items-center p-4 bg-white dark:bg-zinc-900 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-800 transition text-black dark:text-white" id="profile-cart">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-black dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Meu Carrinho</span>
                    </a>
                    
                    <!-- <a href="#" class="flex items-center p-4 bg-white dark:bg-zinc-900 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-800 transition text-black dark:text-white" id="profile-favorites">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-black dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <span>Itens Favoritos</span>
                    </a> -->
                    
                    <a href="rastreio-wide-style.php" class="flex items-center p-4 bg-white dark:bg-zinc-900 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-800 transition text-black dark:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-black dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>Rastreio de Pedidos</span>
                    </a>
                    
                    <button class="flex items-center w-full p-4 bg-white dark:bg-zinc-900 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-800 transition text-black dark:text-white" id="toggle-theme">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-black dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <span>Alternar Tema</span>
                    </button>
                    
                    <button class="flex items-center w-full p-4 bg-gray-100 dark:bg-zinc-900 rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-800 transition text-black dark:text-white" id="logout-button">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-black dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Sair da Conta</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="main.js"></script>
<script src="cookie-consent.js"></script>

<!-- Favorites Modal -->
<div id="favorites-modal" class="full-screen-modal bg-white dark:bg-black">
    <div class="container mx-auto px-4 py-20">
        <button class="close-modal absolute top-6 right-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <h1 class="text-3xl font-bold mb-8">Seus Favoritos</h1>
        
        <div class="favorites-container">
            <!-- Empty favorites message (shown by default, hidden when items are added) -->
            <p class="text-center py-8 text-gray-400" id="empty-favorites-message">Você ainda não tem produtos favoritos.</p>
            
            <!-- Favorites items grid will be dynamically added here -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6" id="favorites-grid">
                <!-- Favorite items will be added here dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Auth Modal -->
<div id="auth-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <div class="flex space-x-4">
                    <button class="text-black dark:text-white font-semibold px-4 py-2 rounded-t-lg tab-active" data-tab="login">Login</button>
                    <button class="text-black dark:text-white font-semibold px-4 py-2 rounded-t-lg" data-tab="register">Registrar</button>
                </div>
                <button class="text-black dark:text-white hover:text-gray-700 dark:hover:text-gray-300" onclick="closeAuthModal()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Login Form -->
            <form id="login-form" class="space-y-4">
                <div>
                    <label class="block text-black dark:text-white text-sm font-medium mb-2" for="login-email">Email</label>
                    <input type="email" id="login-email" class="w-full px-4 py-2 rounded-lg bg-gray-100 dark:bg-zinc-800 text-black dark:text-white border border-gray-300 dark:border-zinc-700 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-black dark:text-white text-sm font-medium mb-2" for="login-password">Senha</label>
                    <input type="password" id="login-password" class="w-full px-4 py-2 rounded-lg bg-gray-100 dark:bg-zinc-800 text-black dark:text-white border border-gray-300 dark:border-zinc-700 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="remember-me" class="rounded text-blue-500 focus:ring-blue-500">
                    <label for="remember-me" class="ml-2 text-sm text-black dark:text-white">Lembrar de mim</label>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-600 transition duration-200">Entrar</button>
            </form>
            
            <!-- Register Form -->
            <form id="register-form" class="space-y-4 hidden">
                <div>
                    <label class="block text-black dark:text-white text-sm font-medium mb-2" for="register-name">Nome Completo</label>
                    <input type="text" id="register-name" class="w-full px-4 py-2 rounded-lg bg-gray-100 dark:bg-zinc-800 text-black dark:text-white border border-gray-300 dark:border-zinc-700 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-black dark:text-white text-sm font-medium mb-2" for="register-email">Email</label>
                    <input type="email" id="register-email" class="w-full px-4 py-2 rounded-lg bg-gray-100 dark:bg-zinc-800 text-black dark:text-white border border-gray-300 dark:border-zinc-700 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-black dark:text-white text-sm font-medium mb-2" for="register-password">Senha</label>
                    <input type="password" id="register-password" class="w-full px-4 py-2 rounded-lg bg-gray-100 dark:bg-zinc-800 text-black dark:text-white border border-gray-300 dark:border-zinc-700 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-black dark:text-white text-sm font-medium mb-2" for="register-confirm-password">Confirmar Senha</label>
                    <input type="password" id="register-confirm-password" class="w-full px-4 py-2 rounded-lg bg-gray-100 dark:bg-zinc-800 text-black dark:text-white border border-gray-300 dark:border-zinc-700 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-600 transition duration-200">Registrar</button>
            </form>
        </div>
    </div>
</div>

<!-- Rastreamento Modal -->
<div id="tracking-modal" class="full-screen-modal bg-white dark:bg-black">
    <div class="container mx-auto px-4 py-20">
        <button class="close-modal absolute top-6 right-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <div class="max-w-xl mx-auto">
            <div class="flex justify-center mb-8">
                <img src="https://i.postimg.cc/QCMM1Byn/WIDE-STYLE-SITE.png" alt="WIDE STYLE" class="h-12 dark:hidden">
                <img src="https://i.postimg.cc/Gm9sQYcb/WIDE-STYLE-SITE-DARK.png" alt="WIDE STYLE" class="h-12 hidden dark:block">
            </div>
            
            <div class="bg-gray-100 dark:bg-zinc-950 p-8 rounded-lg">
                <h2 class="text-2xl font-bold mb-6 text-center text-black dark:text-white">Rastreie seu Pedido</h2>
                
                <p class="mb-6 text-center text-gray-600 dark:text-gray-400">Digite seu código de rastreio e escolha a plataforma para rastrear seu pedido.</p>
                
                <div class="mb-6">
                    <input type="text" id="tracking-code" placeholder="Digite seu código de rastreio" class="w-full px-4 py-3 rounded-lg bg-white dark:bg-zinc-900 text-black dark:text-white border border-gray-300 dark:border-zinc-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="space-y-4">
                    <a href="#" id="track-correios" class="flex items-center p-4 bg-white dark:bg-zinc-900 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 transition text-black dark:text-white">
                        <img src="https://www.correios.com.br/++theme++correios.sitetheme/img/logo-correios.png" alt="Correios" class="h-8 mr-4">
                        <div>
                            <span class="font-medium block">Rastrear via Correios</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Serviço oficial dos Correios do Brasil</span>
                        </div>
                    </a>
                    
                    <a href="#" id="track-melhor-rastreio" class="flex items-center p-4 bg-white dark:bg-zinc-900 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 transition text-black dark:text-white">
                        <img src="https://melhorrastreio.com.br/wp-content/uploads/2021/10/favicon-120x120.png" alt="Melhor Rastreio" class="h-8 mr-4">
                        <div>
                            <span class="font-medium block">Rastrear via Melhor Rastreio</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Rastreie em tempo real e com detalhes</span>
                        </div>
                    </a>
                    
                    <a href="#" id="track-17track" class="flex items-center p-4 bg-white dark:bg-zinc-900 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 transition text-black dark:text-white">
                        <img src="https://s.17track.net/favicon.ico" alt="17Track" class="h-8 mr-4">
                        <div>
                            <span class="font-medium block">Rastrear via 17Track</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Rastreamento internacional e local</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Código para gerenciar o modal de rastreamento
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do modal de rastreamento
    const trackingModal = document.getElementById('tracking-modal');
    const profileModal = document.getElementById('profile-modal');
    const trackingCode = document.getElementById('tracking-code');
    const trackCorreios = document.getElementById('track-correios');
    const trackMelhorRastreio = document.getElementById('track-melhor-rastreio');
    const track17Track = document.getElementById('track-17track');
    
    // Função para abrir o modal de rastreamento
    function openTrackingModal() {
        // Fechar o modal de perfil primeiro
        if (profileModal) {
            profileModal.classList.add('hidden');
        }
        
        // Abrir o modal de rastreamento
        trackingModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    // Função para fechar o modal de rastreamento
    function closeTrackingModal() {
        trackingModal.classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    // Evento para o botão de rastreio de pedidos
    document.getElementById('profile-orders').addEventListener('click', function(e) {
        e.preventDefault();
        openTrackingModal();
    });
    
    // Fechar modal quando o botão de fechar for clicado
    document.querySelector('#tracking-modal .close-modal').addEventListener('click', closeTrackingModal);
    
    // Rastreamento via Correios
    trackCorreios.addEventListener('click', function(e) {
        e.preventDefault();
        const code = trackingCode.value.trim();
        if (code) {
            window.open(`https://rastreamento.correios.com.br/app/index.php?objeto=${code}`, '_blank');
        } else {
            alert('Por favor, insira um código de rastreio.');
        }
    });
    
    // Rastreamento via Melhor Rastreio
    trackMelhorRastreio.addEventListener('click', function(e) {
        e.preventDefault();
        const code = trackingCode.value.trim();
        if (code) {
            window.open(`https://melhorrastreio.com.br/rastreio/${code}`, '_blank');
        } else {
            alert('Por favor, insira um código de rastreio.');
        }
    });
    
    // Rastreamento via 17Track
    track17Track.addEventListener('click', function(e) {
        e.preventDefault();
        const code = trackingCode.value.trim();
        if (code) {
            window.open(`https://t.17track.net/pt#nums=${code}`, '_blank');
        } else {
            alert('Por favor, insira um código de rastreio.');
        }
    });
});
</script>

<!-- Modal de recuperação de senha (forgot password) -->
<div id="forgot-password-modal" class="full-screen-modal fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 hidden">
    <div class="modal-content bg-zinc-900 rounded-lg p-8 w-full max-w-md mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Recuperar Senha</h2>
            <button class="close-modal text-gray-400 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <p class="text-gray-400 mb-6">Informe seu email e enviaremos instruções para redefinir sua senha.</p>
        
        <form id="forgot-password-form">
            <div class="mb-6">
                <label for="forgot-password-email" class="block text-sm mb-2">Email</label>
                <input type="email" id="forgot-password-email" name="email" required
                    class="w-full p-3 bg-zinc-800 border border-gray-700 rounded-md focus:outline-none focus:border-white">
            </div>
            
            <button type="submit" class="w-full bg-white text-black font-medium py-3 rounded-md hover:bg-gray-200 transition">
                Enviar Instruções
            </button>
        </form>
        
        <div class="mt-4 text-center">
            <button class="text-sm text-gray-400 hover:text-white close-modal">Voltar para o login</button>
        </div>
    </div>
</div>

<!-- Modal de alteração de senha (change password) -->
<div id="change-password-modal" class="full-screen-modal fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 hidden">
    <div class="modal-content bg-zinc-900 rounded-lg p-8 w-full max-w-md mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Alterar Senha</h2>
            <button class="close-modal text-gray-400 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form id="change-password-form">
            <div class="mb-4">
                <label for="current-password" class="block text-sm mb-2">Senha Atual</label>
                <input type="password" id="current-password" name="current-password" required
                    class="w-full p-3 bg-zinc-800 border border-gray-700 rounded-md focus:outline-none focus:border-white">
            </div>
            
            <div class="mb-4">
                <label for="change-new-password" class="block text-sm mb-2">Nova Senha</label>
                <input type="password" id="change-new-password" name="new-password" required
                    class="w-full p-3 bg-zinc-800 border border-gray-700 rounded-md focus:outline-none focus:border-white">
            </div>
            
            <div class="mb-6">
                <label for="change-confirm-password" class="block text-sm mb-2">Confirmar Nova Senha</label>
                <input type="password" id="change-confirm-password" name="confirm-password" required
                    class="w-full p-3 bg-zinc-800 border border-gray-700 rounded-md focus:outline-none focus:border-white">
            </div>
            
            <button type="submit" class="w-full bg-white text-black font-medium py-3 rounded-md hover:bg-gray-200 transition">
                Alterar Senha
            </button>
        </form>
    </div>
</div>

<!-- Modal de sessões de usuário -->
<div id="sessions-modal" class="full-screen-modal fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 hidden">
    <div class="modal-content bg-zinc-900 rounded-lg p-8 w-full max-w-md mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Suas Sessões</h2>
            <button class="close-modal text-gray-400 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <p class="text-gray-400 mb-6">Abaixo estão listadas suas sessões ativas. Você pode encerrar qualquer sessão que não reconheça.</p>
        
        <div id="sessions-container" class="max-h-80 overflow-y-auto space-y-4">
            <!-- As sessões serão carregadas dinamicamente aqui -->
        </div>
    </div>
</div>

<!-- Modal de Profile/Conta -->
<div id="profile-modal" class="full-screen-modal fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 hidden">
    <div class="modal-content bg-zinc-900 rounded-lg p-8 w-full max-w-md mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Sua Conta</h2>
            <button class="close-modal text-gray-400 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="flex items-center mb-8">
            <div class="w-16 h-16 bg-white text-black rounded-full flex items-center justify-center text-3xl font-bold mr-4">
                <span class="user-initial">U</span>
            </div>
            <div>
                <h3 class="font-medium text-lg user-name">Usuário</h3>
                <p class="text-gray-400 user-email">usuario@example.com</p>
            </div>
        </div>
        
        <div class="space-y-4">
            <a href="#" id="profile-favorites" class="flex items-center p-3 rounded-md hover:bg-zinc-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span>Favoritos</span>
            </a>
            
            <a href="#" id="profile-orders" class="flex items-center p-3 rounded-md hover:bg-zinc-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span>Meus Pedidos</span>
            </a>
            
            <a href="#" id="profile-cart" class="flex items-center p-3 rounded-md hover:bg-zinc-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>Meu Carrinho</span>
            </a>
            
            <a href="#" id="open-change-password" class="flex items-center p-3 rounded-md hover:bg-zinc-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                <span>Alterar Senha</span>
            </a>
            
            <a href="#" id="open-sessions" class="flex items-center p-3 rounded-md hover:bg-zinc-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span>Gerenciar Sessões</span>
            </a>
        </div>
        
        <div class="mt-8 pt-4 border-t border-gray-800">
            <button id="logout-button" class="flex items-center p-3 rounded-md text-red-500 hover:bg-zinc-800 transition w-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Sair</span>
            </button>
        </div>
    </div>
</div>

<!-- Tracking Modal -->
<div class="tracking-modal fixed inset-0 z-50 flex items-center justify-center hidden">
    // ... existing code ...
</div>

<!-- Forgot Password Modal -->
<div class="forgot-password-modal fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="fixed inset-0 bg-black opacity-50"></div>
    <div class="bg-white dark:bg-zinc-900 rounded-lg p-8 w-full max-w-md relative z-10">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-black dark:text-white">Recuperar senha</h2>
            <button class="close-modal text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <p class="text-gray-600 dark:text-gray-400 mb-4">Digite seu e-mail para receber um link de recuperação de senha.</p>
        
        <form id="forgot-password-form">
            <div class="mb-4">
                <label for="forgot-password-email" class="block text-gray-700 dark:text-gray-300 font-medium mb-2">E-mail</label>
                <input type="email" id="forgot-password-email" class="w-full border border-gray-300 dark:border-gray-700 dark:bg-zinc-800 dark:text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                Enviar link de recuperação
            </button>
        </form>
    </div>
</div>
</body>
</html>