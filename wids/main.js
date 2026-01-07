// Configuração do Supabase
const SUPABASE_URL = 'https://anhjetfewttzpchafqph.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFuaGpldGZld3R0enBjaGFmcXBoIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDUxMTQ5NTEsImV4cCI6MjA2MDY5MDk1MX0.n_H2MU3bsRa0I_m_8KwBXT4KEaxkGnq40eDstq8Sq1Q';

// Função para verificar se o Supabase está funcionando
async function testSupabaseConnection() {
    try {
        const supabase = await initSupabase();
        const { data, error } = await supabase.from('profiles').select('count').limit(1);
        
        if (error) {
            console.error('Erro ao testar conexão com Supabase:', error);
            return false;
        }
        
        console.log('Conexão com Supabase testada com sucesso');
        return true;
    } catch (error) {
        console.error('Erro ao testar conexão:', error);
        return false;
    }
}

// Função para carregar dinamicamente o Supabase
async function initSupabase() {
    console.log('Iniciando inicialização do Supabase...');
    
    if (typeof window.supabase !== 'undefined') {
        console.log('Supabase já está inicializado');
        return window.supabase;
    }

    try {
        // Verificar se já temos o cliente criado pelo script no head
        if (typeof window.supabaseCreateClient === 'function') {
            console.log('Usando supabaseCreateClient do script no head');
            window.supabase = window.supabaseCreateClient(SUPABASE_URL, SUPABASE_KEY);
        } else {
            // Fallback para importação dinâmica
            console.log('Tentando importação dinâmica do Supabase...');
            const { createClient } = await import('https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/+esm');
            window.supabase = createClient(SUPABASE_URL, SUPABASE_KEY);
        }

        // Testar a conexão
        const isConnected = await testSupabaseConnection();
        if (!isConnected) {
            throw new Error('Não foi possível estabelecer conexão com o Supabase');
        }

        console.log('Supabase inicializado e testado com sucesso');
        return window.supabase;
    } catch (error) {
        console.error('Erro detalhado na inicialização do Supabase:', {
            message: error.message,
            stack: error.stack,
            type: error.name
        });
        throw new Error(`Falha ao inicializar Supabase: ${error.message}`);
    }
}

// Funções de autenticação e gerenciamento de sessão
async function registerUserSession(deviceInfo = {}) {
    try {
        const supabase = await initSupabase();
        // Obter usuário atual
        const { data: { user }, error: userError } = await supabase.auth.getUser();
        
        if (userError || !user) {
            return { success: false, error: 'Usuário não autenticado' };
        }
        
        // Registrar a sessão no banco de dados usando a função RPC
        const { data, error } = await supabase.rpc('register_user_session', { 
            device_info: {
                name: deviceInfo.name || navigator.userAgent,
                type: deviceInfo.type || 'web',
                os: deviceInfo.os || navigator.platform
            }
        });
        
        if (error) {
            console.error('Erro ao registrar sessão:', error);
            return { success: false, error: error.message };
        }
        
        // A função retorna o ID da sessão criada
        localStorage.setItem('currentSessionId', data);
        
        return { success: true, sessionId: data };
    } catch (error) {
        console.error('Erro ao registrar sessão:', error);
        return { success: false, error: error.message };
    }
}

async function terminateSession(sessionId) {
    try {
        const supabase = await initSupabase();
        
        const { data, error } = await supabase.rpc('terminate_session', { session_id: sessionId });
        
        if (error) {
            console.error('Erro ao encerrar sessão:', error);
            return { success: false, error: error.message };
        }
        
        if (localStorage.getItem('currentSessionId') === sessionId) {
            localStorage.removeItem('currentSessionId');
        }
        
        return { success: true };
    } catch (error) {
        console.error('Erro ao encerrar sessão:', error);
        return { success: false, error: error.message };
    }
}

async function terminateOtherSessions() {
    try {
        const supabase = await initSupabase();
        const currentSessionId = localStorage.getItem('currentSessionId');
        
        if (!currentSessionId) {
            return { success: false, error: 'Sessão atual não encontrada' };
        }
        
        const { data, error } = await supabase.rpc('terminate_other_sessions', { 
            current_session_id: currentSessionId 
        });
        
        if (error) {
            console.error('Erro ao encerrar outras sessões:', error);
            return { success: false, error: error.message };
        }
        
        return { success: true, count: data };
    } catch (error) {
        console.error('Erro ao encerrar outras sessões:', error);
        return { success: false, error: error.message };
    }
}

async function getUserSessions() {
    try {
        const supabase = await initSupabase();
        
        const { data, error } = await supabase
            .from('user_sessions')
            .select('*')
            .eq('is_active', true)
            .order('last_active', { ascending: false });
        
        if (error) {
            console.error('Erro ao obter sessões:', error);
            return { success: false, error: error.message };
        }
        
        return { success: true, sessions: data };
    } catch (error) {
        console.error('Erro ao obter sessões:', error);
        return { success: false, error: error.message };
    }
}

async function requestPasswordReset(email) {
    try {
        const supabase = await initSupabase();
        
        // No Supabase, podemos usar o método integrado:
        const { error } = await supabase.auth.resetPasswordForEmail(email, {
            redirectTo: window.location.origin + '/reset-password.html',
        });
        
        if (error) {
            console.error('Erro ao solicitar redefinição de senha:', error);
            return { success: false, error: error.message };
        }
        
        // Para usar nossa função personalizada:
        // const { data, error: rpcError } = await supabase.rpc('request_password_reset', { user_email: email });
        
        return { 
            success: true, 
            message: 'Se o email estiver associado a uma conta, você receberá instruções para redefinir sua senha.'
        };
    } catch (error) {
        console.error('Erro ao solicitar redefinição de senha:', error);
        return { 
            success: true, // Por segurança, sempre retornamos success=true 
            message: 'Se o email estiver associado a uma conta, você receberá instruções para redefinir sua senha.'
        };
    }
}

async function resetPasswordWithToken(token, newPassword) {
    try {
        const supabase = await initSupabase();
        
        // Usando a função personalizada do banco de dados
        const { data, error } = await supabase.rpc('reset_password_with_token', {
            token: token,
            new_password: newPassword
        });
        
        if (error) {
            console.error('Erro ao redefinir senha:', error);
            return { success: false, error: error.message };
        }
        
        if (!data) {
            return { success: false, error: 'Token inválido ou expirado' };
        }
        
        return { success: true };
    } catch (error) {
        console.error('Erro ao redefinir senha:', error);
        return { success: false, error: error.message };
    }
}

async function changePassword(currentPassword, newPassword) {
    try {
        const supabase = await initSupabase();
        
        // Usando a API nativa do Supabase
        const { error } = await supabase.auth.updateUser({
            password: newPassword
        });
        
        if (error) {
            console.error('Erro ao alterar senha:', error);
            return { success: false, error: error.message };
        }
        
        // Podemos ainda adicionar nosso log de atividade
        await supabase.rpc('log_activity', {
            activity_type: 'password_changed',
            details: {}
        });
        
        return { success: true };
    } catch (error) {
        console.error('Erro ao alterar senha:', error);
        return { success: false, error: error.message };
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const promoBanner = document.getElementById('promo-banner');
    const header = document.querySelector('header');
    
    let lastScrollTop = 0;
    let bannerHeight = promoBanner.offsetHeight;
    
    window.addEventListener('scroll', function() {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > bannerHeight) {
            // Rolando para baixo - esconder o banner
            promoBanner.style.transform = 'translateY(-100%)';
            header.style.top = '0';
        } else {
            // Rolando para cima - mostrar o banner
            promoBanner.style.transform = 'translateY(0)';
            header.style.top = bannerHeight + 'px';
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }, false);

        // Product data
        const products = [
            {
                id: 1,
                name: "Divine Steps Oversized",
                price: 199.90,
                category: "Camisetas",
                images: {
                    front: "https://i.postimg.cc/6QM9f6x7/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-2.png",
                    back: "https://i.postimg.cc/zBM84dB8/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-3.png"
                },
                description: "Camiseta oversized branca unissex com estampa traseira em estilo gravura antiga, retratando São João Batista batizando Jesus Cristo no rio Jordão, com a pomba do Espírito Santo e raios de luz acima. Paisagem com água e rochas ao fundo. Tecido confortável, ideal para estilo com significado espiritual.",
                sizes: ["P", "M", "G", "GG"],
                colors: ["white", "gray", "black"]
            },
            {
                id: 2,
                name: "São Miguel Arcanjo",
                price: 249.90,
                category: "Moletons",
                images: {
                    front: "https://i.postimg.cc/dtcL11sv/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-4.png",
                    back: "https://i.postimg.cc/CK5KtnNC/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-5.png"
                },
                description: "Moletom preto unissex com estampa traseira em estilo gravura detalhada, retratando o Arcanjo Miguel derrotando o demônio. O arcanjo, com asas abertas e armadura, pisa sobre a figura derrotada, segurando uma lança. Detalhes como penas caindo e a expressão de luta complementam a cena. Feito em tecido confortável, ideal para estilo com simbolismo espiritual.",
                sizes: ["P", "M", "G", "GG"],
                colors: ["black", "gray"]
            },
            {
                id: 3,
                name: "Crown of Belief",
                price: 199.90,
                category: "Camisas",
                images: {
                    front: "https://i.postimg.cc/gc82fWv7/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-6.png",
                    back: "https://i.postimg.cc/C5BB5GQG/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-4.png"
                },
                description: "Camisa preta unissex com estampa traseira minimalista, apresentando uma coroa de espinhos em círculo. Ao redor, a frase CAMINHANDO PELA FÉ, NÃO PELA VISTA em letras estilizadas, e abaixo, a referência bíblica 2 CORÍNTIOS 5:7. Feita em tecido confortável, perfeita para estilo com mensagem espiritual.",
                sizes: ["P", "M", "G", "GG"],
                colors: ["black", "gray"]
            },
            {
                id: 4,
                name: "Rebel Art",
                price: 249.90,
                category: "Moletons",
                images: {
                    front: "https://i.postimg.cc/85WTQRrv/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-8.png",
                    back: "https://i.postimg.cc/kGv9D7sX/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-9.png"
                },
                description: "Moletom unissex com estampa traseira em estilo grafite, com a palavra SANTIFICA em letras estilizadas e efeito de tinta escorrendo. Tecido confortável, perfeito para um look urbano e espiritual.",
                sizes: ["P", "M", "G", "GG"],
                colors: ["white", "black", "gray"]
            },
            {
                id: 5,
                name: "in love we trust Tee",
                price: 169.90,
                category: "Camisas",
                images: {
                    front: "https://i.postimg.cc/6QM9f6x7/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-2.png",
                    back: "https://i.postimg.cc/9fwhZdbk/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-10.png"
                },
                description: "Camiseta branca unissez com estampa in love we trust Tee",
                sizes: ["P", "M", "G", "GG"],
                colors: ["white", "black"]
            },
            {
                id: 6,
                name: "Godly Expression",
                price: 229.90,
                category: "Camisas",
                images: {
                    front: "https://i.postimg.cc/gc82fWv7/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-6.png",
                    back: "https://i.postimg.cc/0jbmmRbJ/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-2.png"
                },
                description: "Camisa oversized preta unissex, GODLY EXPRESSION Tecido confortável, perfeito para um look urbano e espiritual.",
                sizes: ["P", "M", "G", "GG"],
                colors: ["black", "white", "gray"]
            },
            {
                id: 7,
                name: "Natus Vincere",
                price: 189.90,
                category: "Camisetas",
                images: {
                    front: "https://i.postimg.cc/6QM9f6x7/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-2.png",
                    back: "https://i.postimg.cc/8zjQgR2C/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-12.png"
                },
                description: "camisa oversized preta unissex, Natus Vincere Tecido confortável, perfeito para um look urbano.",
                sizes: ["P", "M", "G", "GG"],
                colors: ["white", "black"]
            },
            {
                id: 8,
                name: "True Love",
                price: 219.90,
                category: "Camisetas",
                images: {
                    front: "https://i.postimg.cc/pr24GzQR/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-13.png",
                    back: "https://i.postimg.cc/qqjY9qQn/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-14.png"
                },
                description: "moletom branco unissex, True Love Tecido confortável, perfeito para um look urbano.",
                sizes: ["P", "M", "G", "GG"],
                colors: ["white", "black"]
            },
            {
                id: 9,
                name: "Rogue Script",
                price: 219.90,
                category: "Moletons",
                images: {
                    front: "https://i.postimg.cc/VLZpgSJP/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-15.png",
                    back: "https://i.postimg.cc/cLVjqV7D/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-16.png"
                },
                description: "moletom preto unissex, Rogue Script Tecido confortável, perfeito para um look urbano.",
                sizes: ["P", "M", "G", "GG"],
                colors: ["black", "white"]
            },
            {
                id: 10,
                name: "God in My Heart",
                price: 189.90,
                category: "Camisetas",
                images: {
                    front: "https://i.postimg.cc/6QM9f6x7/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-2.png",
                    back: "https://i.postimg.cc/7hW3QPpV/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-3.png"
                    },
                    description: "Camiseta com estampa minimalista. Design clean e elegante para um visual sofisticado.",
                sizes: ["P", "M", "G", "GG"],
                colors: ["white", "black"]
            },
            {
                id: 11,
                name: "Jesus Never Abandons Tee",
                price: 219.90,
                category: "Moletons",
                images: {
                    front: "https://i.postimg.cc/dtcL11sv/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-4.png",
                    back: "https://i.postimg.cc/nh87sWZC/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt.png"
                },
                description: "moletom preto unissex, Jesus Never Abandons Tee Tecido confortável, perfeito para um look urbano.",
                sizes: ["P", "M", "G", "GG"],
                colors: ["white", "black"]
            },
            {
                id: 12,
                name: "Saved by Grace",
                price: 219.90,
                category: "Moletons",
                images: {
                    front: "https://i.postimg.cc/85WTQRrv/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-8.png",
                    back: "https://i.postimg.cc/Kv3B8266/Black-White-Grunge-Minimal-Typographic-Christian-Religious-T-Shirt-1.png"
                    },
                    description: "moletom branco unissex, Saved by Grace Tecido confortável, perfeito para um look urbano.",
                sizes: ["P", "M", "G", "GG"],
                colors: ["white", "black"]
            }   
        ];
        
        // DOM Elements
        const cartToggle = document.getElementById('cart-toggle');
        const mobileCartToggle = document.getElementById('mobile-cart-toggle');
        const cartModal = document.getElementById('cart-modal');
        const productDetailModal = document.getElementById('product-detail-modal');
        const checkoutModal = document.getElementById('checkout-modal');
        const orderConfirmationModal = document.getElementById('order-confirmation-modal');
        const favoritesModal = document.getElementById('favorites-modal');
        const mobileMenu = document.getElementById('mobile-menu');
        const searchBar = document.getElementById('search-bar');
        const cartCountBadge = cartToggle.querySelector('span');
        const emptyCartMessage = document.getElementById('empty-cart-message');
        const cartItems = document.querySelector('.cart-items');
        const cartSubtotal = document.getElementById('cart-subtotal');
        const cartTotal = document.getElementById('cart-total');
        const checkoutSubtotal = document.getElementById('checkout-subtotal');
        const checkoutTotal = document.getElementById('checkout-total');
        const checkoutItems = document.getElementById('checkout-items');
        const mobileBottomNav = document.querySelector('.mobile-bottom-nav');
        const categoryFilters = document.querySelectorAll('.category-filter');
        const loadMoreBtn = document.getElementById('load-more');
        const floatingAddBtn = document.querySelector('.floating-add-btn');
        const emptyFavoritesMessage = document.getElementById('empty-favorites-message');
        const favoritesGrid = document.getElementById('favorites-grid');
        
        // Show mobile bottom navigation
        if (window.innerWidth <= 768) {
            mobileBottomNav.classList.remove('hidden');
        }
        
        // Mobile image rotation
        const setupMobileImageRotation = () => {
            if (window.innerWidth <= 768) {
                const productContainers = document.querySelectorAll('.product-image-container');
                
                productContainers.forEach(container => {
                    const frontImage = container.querySelector('.product-image-front');
                    const backImage = container.querySelector('.product-image-back');
                    let isRotating = true;
                    let rotationTimeout;
                    
                    const rotateImages = () => {
                        if (!isRotating) return;
                        
                        if (frontImage.style.opacity === '0') {
                            frontImage.style.opacity = '1';
                            backImage.style.opacity = '0';
                            rotationTimeout = setTimeout(rotateImages, 5000); // Show front for 5s
                        } else {
                            frontImage.style.opacity = '0';
                            backImage.style.opacity = '1';
                            rotationTimeout = setTimeout(rotateImages, 5000); // Show back for 5s
                        }
                    };
                    
                    // Start the rotation after 5s
                    rotationTimeout = setTimeout(rotateImages, 5000);
                    
                    // Clean up on container removal
                    const observer = new MutationObserver((mutations) => {
                        mutations.forEach((mutation) => {
                            if (mutation.type === 'childList' && !document.contains(container)) {
                                isRotating = false;
                                clearTimeout(rotationTimeout);
                                observer.disconnect();
                            }
                        });
                    });
                    
                    observer.observe(document.body, { childList: true, subtree: true });
                });
            }
        };
        
        // Initialize mobile image rotation
        setupMobileImageRotation();
        
        // Window resize event for mobile image rotation and mobile navigation
        window.addEventListener('resize', () => {
            setupMobileImageRotation();
            
            if (window.innerWidth <= 768) {
                mobileBottomNav.classList.remove('hidden');
            } else {
                mobileBottomNav.classList.add('hidden');
            }
        });
        
        // Cart functionality
        let cart = JSON.parse(localStorage.getItem('wideStyleCart')) || [];
        
        // Update cart count
        const updateCartCount = () => {
            const count = cart.reduce((total, item) => total + item.quantity, 0);
            cartCountBadge.textContent = count;
        };
        
        // Format price
        const formatPrice = (price) => {
            return `R$ ${price.toFixed(2).replace('.', ',')}`;
        };
        
        // Calculate subtotal
        const calculateSubtotal = () => {
            return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
        };
        
        // Update cart display
        const updateCartDisplay = () => {
            // Update cart count badge
            updateCartCount();
            
            // Clear cart items container
            cartItems.innerHTML = '';
            
            // Show/hide empty cart message
            if (cart.length === 0) {
                emptyCartMessage.classList.remove('hidden');
            } else {
                emptyCartMessage.classList.add('hidden');
                
                // Add each cart item to the display
                cart.forEach(item => {
                    const cartItemElement = document.createElement('div');
                    cartItemElement.className = 'flex border-b border-gray-800 pb-6';
                    cartItemElement.innerHTML = `
                        <div class="w-24 h-24 bg-black bg-opacity-70 mr-4 rounded-md overflow-hidden">
                            <img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between mb-2">
                                <h3 class="font-medium">${item.name}</h3>
                                <button class="text-gray-400 hover:text-white remove-from-cart" data-id="${item.id}" data-color="${item.color}" data-size="${item.size}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-gray-400 text-sm mb-2">Tamanho: ${item.size} | Cor: ${item.color}</p>
                            <div class="flex justify-between items-center">
                                <div class="flex items-center border border-gray-800 rounded-md">
                                    <button class="w-8 h-8 flex items-center justify-center hover:bg-gray-800 decrease-item-quantity rounded-l-md" data-id="${item.id}" data-color="${item.color}" data-size="${item.size}">-</button>
                                    <span class="w-8 text-center">${item.quantity}</span>
                                    <button class="w-8 h-8 flex items-center justify-center hover:bg-gray-800 increase-item-quantity rounded-r-md" data-id="${item.id}" data-color="${item.color}" data-size="${item.size}">+</button>
                                </div>
                                <span>${formatPrice(item.price * item.quantity)}</span>
                            </div>
                        </div>
                    `;
                    cartItems.appendChild(cartItemElement);
                });
                
                // Add event listeners to the newly created buttons
                document.querySelectorAll('.remove-from-cart').forEach(button => {
                    button.addEventListener('click', function() {
                        const id = parseInt(this.dataset.id);
                        const color = this.dataset.color;
                        const size = this.dataset.size;
                        removeFromCart(id, color, size);
                    });
                });
                
                document.querySelectorAll('.decrease-item-quantity').forEach(button => {
                    button.addEventListener('click', function() {
                        const id = parseInt(this.dataset.id);
                        const color = this.dataset.color;
                        const size = this.dataset.size;
                        updateCartItemQuantity(id, color, size, -1);
                    });
                });
                
                document.querySelectorAll('.increase-item-quantity').forEach(button => {
                    button.addEventListener('click', function() {
                        const id = parseInt(this.dataset.id);
                        const color = this.dataset.color;
                        const size = this.dataset.size;
                        updateCartItemQuantity(id, color, size, 1);
                    });
                });
            }
            
            // Update subtotal and total
            const subtotal = calculateSubtotal();
            cartSubtotal.textContent = formatPrice(subtotal);
            cartTotal.textContent = formatPrice(subtotal);
            
            // Update checkout display if it exists
            if (checkoutSubtotal && checkoutTotal) {
                checkoutSubtotal.textContent = formatPrice(subtotal);
                checkoutTotal.textContent = formatPrice(subtotal);
                
                // Update checkout items
                updateCheckoutItems();
            }
            
            // Save cart to localStorage
            localStorage.setItem('wideStyleCart', JSON.stringify(cart));
        };
        
        // Update checkout items display
        const updateCheckoutItems = () => {
            if (!checkoutItems) return;
            
            checkoutItems.innerHTML = '';
            
            cart.forEach(item => {
                const checkoutItemElement = document.createElement('div');
                checkoutItemElement.className = 'flex items-center mb-4';
                checkoutItemElement.innerHTML = `
                    <div class="w-16 h-16 bg-black bg-opacity-70 mr-4 rounded-md overflow-hidden">
                        <img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <h3 class="font-medium text-sm">${item.name}</h3>
                        <p class="text-gray-400 text-xs">Tamanho: ${item.size} | Cor: ${item.color} | Qtd: ${item.quantity}</p>
                    </div>
                    <div class="text-right">
                        <span>${formatPrice(item.price * item.quantity)}</span>
                    </div>
                `;
                checkoutItems.appendChild(checkoutItemElement);
            });
        };
        
        // Add to cart
        const addToCart = (productId, quantity = 1, size = 'M', color = 'black') => {
            const product = products.find(p => p.id === productId);
            
            if (!product) return;
            
            // Check if the product with the same size and color already exists in the cart
            const existingItemIndex = cart.findIndex(item => 
                item.id === productId && item.size === size && item.color === color
            );
            
            if (existingItemIndex !== -1) {
                // Update quantity of existing item
                cart[existingItemIndex].quantity += quantity;
            } else {
                // Add new item to cart
                cart.push({
                    id: productId,
                    name: product.name,
                    price: product.price,
                    image: product.images.front,
                    quantity: quantity,
                    size: size,
                    color: color
                });
            }
            
            // Update cart display
            updateCartDisplay();
            
            // Show notification
            showNotification(`${product.name} adicionado ao carrinho!`, 'success');
        };
        
        // Remove from cart
        const removeFromCart = (productId, color, size) => {
            cart = cart.filter(item => !(item.id === productId && item.color === color && item.size === size));
            updateCartDisplay();
        };
        
        // Update cart item quantity
        const updateCartItemQuantity = (productId, color, size, change) => {
            const itemIndex = cart.findIndex(item => 
                item.id === productId && item.color === color && item.size === size
            );
            
            if (itemIndex !== -1) {
                cart[itemIndex].quantity += change;
                
                // Remove item if quantity is 0 or less
                if (cart[itemIndex].quantity <= 0) {
                    removeFromCart(productId, color, size);
                } else {
                    updateCartDisplay();
                }
            }
        };
        
        // Show notification
        const showNotification = (message, type = 'success') => {
            // Check if notification container exists, if not create it
            let notificationContainer = document.getElementById('notification-container');
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification ${type} p-4 mb-3 rounded-lg shadow-lg flex items-center`;
            
            // Add notification content
            notification.innerHTML = `
                <div class="mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        ${type === 'success' 
                            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />'
                            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />'}
                    </svg>
                </div>
                <div>${message}</div>
            `;
            
            // Add to container
            notificationContainer.appendChild(notification);
            
            // Show notification with animation
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        };
        
        // Open modal
        const openModal = (modal) => {
            // Close all modals first
            document.querySelectorAll('.full-screen-modal').forEach(m => {
                m.classList.remove('active');
            });
            
            // Open the requested modal
            modal.classList.add('active');
            
            // Prevent body scrolling
            document.body.style.overflow = 'hidden';
        };
        
        // Close modal
        const closeModal = (modal) => {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        };
        
        // Product detail functionality
        const openProductDetail = (productId) => {
            const product = products.find(p => p.id === productId);
            
            if (!product) return;
            
            // Update product detail modal with product information
            document.getElementById('product-title').textContent = product.name;
            document.getElementById('product-price').textContent = formatPrice(product.price);
            document.getElementById('product-description').textContent = product.description;
            document.getElementById('main-product-image').src = product.images.front;
            document.getElementById('modal-add-to-cart').dataset.product = productId;
            
            // Update product thumbnails
            const thumbnails = document.querySelectorAll('.product-thumbnail');
            if (thumbnails.length >= 2) {
                thumbnails[0].dataset.image = product.images.front;
                thumbnails[0].querySelector('img').src = product.images.front;
                
                thumbnails[1].dataset.image = product.images.back;
                thumbnails[1].querySelector('img').src = product.images.back;
            }
            
            // Reset quantity to 1
            document.getElementById('product-quantity').textContent = '1';
            
            // Define disabled colors and color order for each product
            const productColorConfig = {
                1: {
                    disabled: ['black', 'gray'],
                    order: ['white', 'black', 'gray']
                },
                2: {
                    disabled: ['gray', 'white'],
                    order: ['black', 'gray', 'white']
                },
                3: {
                    disabled: ['gray', 'white'],
                    order: ['black', 'gray', 'white']
                },
                4: {
                    disabled: ['black', 'gray'],
                    order: ['white', 'black', 'gray']
                },
                5: {
                    disabled: ['black', 'white'],
                    order: ['black', 'gray', 'white']
                },
                6: {
                    disabled: ['white', 'gray'],
                    order: ['black', 'white', 'gray']
                },
                7: {
                    disabled: ['black', 'gray'],
                    order: ['white', 'black', 'gray']
                },
                8: {
                    disabled: ['black', 'gray'],
                    order: ['white', 'black', 'gray']
                },
                9: {
                    disabled: ['white', 'gray'],
                    order: ['black', 'white', 'gray']
                },
                10: {
                    disabled: ['black', 'gray'],
                    order: ['white', 'black', 'gray']
                },
                11: {
                    disabled: ['white', 'gray'],
                    order: ['black', 'white', 'gray']
                },
                12: {
                    disabled: ['black', 'gray'],
                    order: ['white', 'black', 'gray']
                }
            };
            
            // Define all available colors with their properties
            const colorProperties = {
                black: { bgColor: 'bg-black' },
                gray: { bgColor: 'bg-gray-700' },
                white: { bgColor: 'bg-gray-300' }
            };
            
            // Get configuration for current product
            const config = productColorConfig[productId] || {
                disabled: [],
                order: ['black', 'gray', 'white']
            };
            
            // Clear existing color options
            const colorContainer = document.getElementById('product-colors');
            colorContainer.innerHTML = '';
            
            // Create color options in the specified order
            config.order.forEach(colorName => {
                const isDisabled = config.disabled.includes(colorName);
                const button = document.createElement('button');
                button.className = `color-option w-8 h-8 ${colorProperties[colorName].bgColor} border border-gray-700 rounded-full hover:border-white transition relative`;
                button.dataset.color = colorName;
                
                if (isDisabled) {
                    button.style.opacity = '0.5';
                    button.style.cursor = 'not-allowed';
                    
                    // Add disabled icon SVG
                    const disabledIcon = document.createElement('div');
                    disabledIcon.className = 'disabled-icon absolute top-0 left-0 w-full h-full flex items-center justify-center';
                    disabledIcon.innerHTML = `
                        <svg class="w-4 h-4 text-red-500" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" 
                                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    `;
                    button.appendChild(disabledIcon);
                }
                
                colorContainer.appendChild(button);
            });
            
            // Select first available size and color by default
            const firstSize = document.querySelector('.size-option');
            if (firstSize) {
                firstSize.classList.remove('border-gray-600');
                firstSize.classList.add('border-white');
            }
            
            const firstAvailableColor = document.querySelector('.color-option:not(.disabled-icon)');
            if (firstAvailableColor) {
                firstAvailableColor.classList.remove('border-gray-700');
                firstAvailableColor.classList.add('border-white');
            }
            
            // Open the modal
            openModal(productDetailModal);
        };
        
        // Initialize cart display
        updateCartDisplay();
        
        // Event Listeners
        
        // Menu toggle
        document.getElementById('menu-toggle').addEventListener('click', () => {
            mobileMenu.classList.remove('hidden');
        });
        
        document.getElementById('close-menu').addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
        });
        
        // Search toggle
        document.getElementById('search-toggle').addEventListener('click', () => {
            searchBar.classList.remove('hidden');
        });
        
        document.getElementById('close-search').addEventListener('click', () => {
            searchBar.classList.add('hidden');
        });
        
        // Cart toggle
        cartToggle.addEventListener('click', () => {
            openModal(cartModal);
        });
        
        // Mobile cart toggle
        if (mobileCartToggle) {
            mobileCartToggle.addEventListener('click', () => {
                openModal(cartModal);
            });
        }
        
        // Close modals
        document.querySelectorAll('.close-modal').forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.full-screen-modal');
                closeModal(modal);
            });
        });
        
        // Quick view buttons
        document.querySelectorAll('.quick-view').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const productId = parseInt(this.dataset.product);
                openProductDetail(productId);
            });
        });
        
        // Add to cart buttons
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const productId = parseInt(this.dataset.product);
                addToCart(productId);
            });
        });
        
        // Floating add to cart button
        if (floatingAddBtn) {
            floatingAddBtn.addEventListener('click', function() {
                // Scroll to products section
                const productsSection = document.getElementById('products');
                if (productsSection) {
                    productsSection.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }
        
        // Modal add to cart button
        document.getElementById('modal-add-to-cart').addEventListener('click', function() {
            const productId = parseInt(this.dataset.product);
            const quantity = parseInt(document.getElementById('product-quantity').textContent);
            const selectedSize = document.querySelector('#product-sizes .border-white').textContent;
            const selectedColor = document.querySelector('#product-colors .border-white').dataset.color;
            
            addToCart(productId, quantity, selectedSize, selectedColor);
            closeModal(productDetailModal);
        });
        
        // Product quantity controls
        document.getElementById('decrease-quantity').addEventListener('click', function() {
            const quantityElement = document.getElementById('product-quantity');
            let quantity = parseInt(quantityElement.textContent);
            if (quantity > 1) {
                quantityElement.textContent = quantity - 1;
            }
        });
        
        document.getElementById('increase-quantity').addEventListener('click', function() {
            const quantityElement = document.getElementById('product-quantity');
            let quantity = parseInt(quantityElement.textContent);
            quantityElement.textContent = quantity + 1;
        });
        
        // Size selection
        document.querySelectorAll('.size-option').forEach(button => {
            button.addEventListener('click', function() {
                // Remove selected class from all size options
                document.querySelectorAll('.size-option').forEach(btn => {
                    btn.classList.remove('border-white');
                    btn.classList.add('border-gray-600');
                });
                
                // Add selected class to clicked button
                this.classList.remove('border-gray-600');
                this.classList.add('border-white');
            });
        });
        
        // Color selection
        document.querySelectorAll('.color-option').forEach(button => {
            button.addEventListener('click', function() {
                // Check if color is disabled
                if (this.querySelector('.disabled-icon')) {
                    return; // Don't allow selection of disabled colors
                }
                
                // Remove selected class from all color options
                document.querySelectorAll('.color-option').forEach(btn => {
                    btn.classList.remove('border-white');
                    btn.classList.add('border-gray-700');
                });
                
                // Add selected class to clicked button
                this.classList.remove('border-gray-700');
                this.classList.add('border-white');
            });
        });
        
        // Product thumbnails
        document.querySelectorAll('.product-thumbnail').forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                const mainImage = document.getElementById('main-product-image');
                mainImage.src = this.dataset.image;
            });
        });
        
        // Tab functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                
                // Remove active class from all tab buttons and content
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('border-white', 'active');
                    btn.classList.add('text-gray-400');
                });
                
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Add active class to clicked tab button and show content
                this.classList.remove('text-gray-400');
                this.classList.add('border-white', 'active');
                
                document.getElementById(`${tabId}-tab`).classList.remove('hidden');
            });
        });
        
        // Category filter functionality
        categoryFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                // Remove active class from all filters
                categoryFilters.forEach(f => {
                    f.classList.remove('bg-white', 'text-black');
                    f.classList.add('bg-zinc-900', 'hover:bg-zinc-800');
                });
                
                // Add active class to clicked filter
                this.classList.remove('bg-zinc-900', 'hover:bg-zinc-800');
                this.classList.add('bg-white', 'text-black');
                
                // Filter products logic would go here
                // For now, just show a notification
                showNotification(`Filtro aplicado: ${this.textContent}`, 'success');
            });
        });
        
        // Proceed to checkout
        document.getElementById('proceed-to-checkout').addEventListener('click', function() {
            if (cart.length === 0) {
                showNotification('Seu carrinho está vazio.', 'error');
                return;
            }
            
            closeModal(cartModal);
            openModal(checkoutModal);
            updateCheckoutItems();
        });
        
        // Back to cart
        document.getElementById('back-to-cart').addEventListener('click', function() {
            closeModal(checkoutModal);
            openModal(cartModal);
        });
        
        // Checkout navigation
        document.getElementById('continue-to-shipping').addEventListener('click', function() {
            const infoSection = document.getElementById('info-section');
            const shippingSection = document.getElementById('shipping-section');
            const progressSteps = document.querySelectorAll('.flex.justify-between.mb-12 .w-8.h-8');
            
            // Validate form
            const inputs = infoSection.querySelectorAll('input[required], select[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value) {
                    input.classList.add('border-red-500');
                    isValid = false;
                } else {
                    input.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                showNotification('Por favor, preencha todos os campos obrigatórios.', 'error');
                return;
            }
            
            infoSection.classList.add('hidden');
            shippingSection.classList.remove('hidden');
            
            // Update progress indicator
            if (progressSteps[1]) {
                progressSteps[1].classList.remove('bg-gray-800');
                progressSteps[1].classList.add('bg-white', 'text-black');
            }
        });
        
        document.getElementById('back-to-info').addEventListener('click', function() {
            const infoSection = document.getElementById('info-section');
            const shippingSection = document.getElementById('shipping-section');
            const progressSteps = document.querySelectorAll('.flex.justify-between.mb-12 .w-8.h-8');
            
            shippingSection.classList.add('hidden');
            infoSection.classList.remove('hidden');
            
            // Update progress indicator
            if (progressSteps[1]) {
                progressSteps[1].classList.add('bg-gray-800');
                progressSteps[1].classList.remove('bg-white', 'text-black');
            }
        });
        
        document.getElementById('continue-to-payment').addEventListener('click', function() {
            const shippingSection = document.getElementById('shipping-section');
            const paymentSection = document.getElementById('payment-section');
            const progressSteps = document.querySelectorAll('.flex.justify-between.mb-12 .w-8.h-8');
            
            shippingSection.classList.add('hidden');
            paymentSection.classList.remove('hidden');
            
            // Update progress indicator
            if (progressSteps[2]) {
                progressSteps[2].classList.remove('bg-gray-800');
                progressSteps[2].classList.add('bg-white', 'text-black');
            }
        });
        
        document.getElementById('back-to-shipping').addEventListener('click', function() {
            const shippingSection = document.getElementById('shipping-section');
            const paymentSection = document.getElementById('payment-section');
            const progressSteps = document.querySelectorAll('.flex.justify-between.mb-12 .w-8.h-8');
            
            paymentSection.classList.add('hidden');
            shippingSection.classList.remove('hidden');
            
            // Update progress indicator
            if (progressSteps[2]) {
                progressSteps[2].classList.add('bg-gray-800');
                progressSteps[2].classList.remove('bg-white', 'text-black');
            }
        });
        
        // Shipping option selection
        const shippingOptions = document.querySelectorAll('input[name="shipping"]');
        const checkoutShipping = document.getElementById('checkout-shipping');
        
        if (shippingOptions.length > 0) {
            shippingOptions.forEach(option => {
                option.addEventListener('change', function() {
                    const isExpress = this.parentElement.textContent.includes('Expressa');
                    const shippingCost = isExpress ? 29.90 : 0;
                    const subtotal = calculateSubtotal();
                    
                    if (checkoutShipping) {
                        checkoutShipping.textContent = isExpress ? formatPrice(shippingCost) : 'Grátis';
                    }
                    
                    if (checkoutTotal) {
                        checkoutTotal.textContent = formatPrice(subtotal + shippingCost);
                    }
                });
            });
        }
        
        // Payment method selection
        const paymentRadios = document.querySelectorAll('input[name="payment-method"]');
        const paymentDetails = document.querySelectorAll('#payment-section .payment-details');
        
        if (paymentRadios.length > 0 && paymentDetails.length > 0) {
            paymentRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Hide all payment details
                    paymentDetails.forEach(detail => {
                        detail.classList.add('hidden');
                    });
                    
                    // Show selected payment details
                    if (this.checked) {
                        const selectedDetails = this.parentElement.nextElementSibling;
                        if (selectedDetails && selectedDetails.classList.contains('payment-details')) {
                            selectedDetails.classList.remove('hidden');
                        }
                    }
                });
            });
        }
        
        // Place order
        document.getElementById('place-order').addEventListener('click', function() {
            const paymentSection = document.getElementById('payment-section');
            
            // Validate form
            const inputs = paymentSection.querySelectorAll('input[required], select[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value) {
                    input.classList.add('border-red-500');
                    isValid = false;
                } else {
                    input.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                showNotification('Por favor, preencha todos os campos obrigatórios.', 'error');
                return;
            }
            
            // Get selected payment method
            const selectedPayment = document.querySelector('input[name="payment-method"]:checked');
            const paymentMethod = selectedPayment ? selectedPayment.id.replace('-payment', '') : '';
            
            // Create order object
            const order = {
                id: 'ORD' + Date.now(),
                date: new Date().toISOString(),
                items: cart,
                subtotal: calculateSubtotal(),
                shipping: document.getElementById('checkout-shipping').textContent === 'Grátis' ? 0 : 29.90,
                total: parseFloat(document.getElementById('checkout-total').textContent.replace('R$ ', '').replace(',', '.')),
                paymentMethod: paymentMethod,
                customer: {
                    firstName: document.getElementById('first-name').value,
                    lastName: document.getElementById('last-name').value,
                    email: document.getElementById('email').value,
                    address: document.getElementById('address').value,
                    city: document.getElementById('city').value,
                    state: document.getElementById('state').value,
                    zip: document.getElementById('zip').value,
                    phone: document.getElementById('phone').value
                }
            };
            
            // Save order to localStorage
            const orders = JSON.parse(localStorage.getItem('wideStyleOrders')) || [];
            orders.push(order);
            localStorage.setItem('wideStyleOrders', JSON.stringify(orders));
            
            // Update order confirmation modal
            document.getElementById('order-number').textContent = order.id;
            document.getElementById('order-date').textContent = new Date().toLocaleDateString('pt-BR');
            document.getElementById('order-email').textContent = order.customer.email;
            document.getElementById('order-total').textContent = formatPrice(order.total);
            
            // Clear cart
            cart = [];
            localStorage.setItem('wideStyleCart', JSON.stringify(cart));
            updateCartDisplay();
            
            // Show order confirmation
            closeModal(checkoutModal);
            openModal(orderConfirmationModal);
        });
        
        // Continue shopping after order
        document.getElementById('continue-shopping').addEventListener('click', function() {
            closeModal(orderConfirmationModal);
        });
        
        // Apply coupon in cart
        document.getElementById('apply-coupon').addEventListener('click', function() {
            const couponInput = document.getElementById('coupon');
            const couponCode = couponInput.value.trim().toUpperCase();
            
            // Simple coupon validation
            if (couponCode === 'WIDE10') {
                const subtotal = calculateSubtotal();
                const discount = subtotal * 0.1; // 10% discount
                const newTotal = subtotal - discount;
                
                // Update total
                cartTotal.textContent = formatPrice(newTotal);
                
                showNotification('Cupom aplicado com sucesso!', 'success');
                couponInput.disabled = true;
                this.disabled = true;
                this.textContent = 'Aplicado';
            } else {
                showNotification('Cupom inválido ou expirado.', 'error');
            }
        });
        
        // Apply coupon in checkout
        document.getElementById('apply-checkout-coupon').addEventListener('click', function() {
            const couponInput = document.getElementById('checkout-coupon');
            const couponCode = couponInput.value.trim().toUpperCase();
            
            // Simple coupon validation
            if (couponCode === 'WIDE10') {
                const subtotal = calculateSubtotal();
                const discount = subtotal * 0.1; // 10% discount
                const shippingText = document.getElementById('checkout-shipping').textContent;
                const shippingCost = shippingText === 'Grátis' ? 0 : 29.90;
                const newTotal = subtotal - discount + shippingCost;
                
                // Update total
                checkoutTotal.textContent = formatPrice(newTotal);
                
                showNotification('Cupom aplicado com sucesso!', 'success');
                couponInput.disabled = true;
                this.disabled = true;
                this.textContent = 'Aplicado';
            } else {
                showNotification('Cupom inválido ou expirado.', 'error');
            }
        });
        
        // Section navigation
        document.querySelectorAll('[data-section]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.dataset.section;
                
                // Close mobile menu if open
                if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.add('hidden');
                }
                
                // Scroll to section
                if (section === 'home') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    const targetSection = document.getElementById(section);
                    if (targetSection) {
                        targetSection.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });

        // Video controls
        const video = document.getElementById('mainVideo');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const playIcon = document.getElementById('playIcon');
        const pauseIcon = document.getElementById('pauseIcon');

        playPauseBtn.addEventListener('click', () => {
            if (video.paused) {
                video.play();
                playIcon.classList.add('hidden');
                pauseIcon.classList.remove('hidden');
            } else {
                video.pause();
                playIcon.classList.remove('hidden');
                pauseIcon.classList.add('hidden');
            }
        });

        // Account Modal Functionality
        const accountModal = document.getElementById('account-modal');
        const accountToggle = document.getElementById('account-toggle');
        const mobileAccountToggle = document.getElementById('mobile-account-toggle');
        const closeModalButtons = document.querySelectorAll('.close-modal');
        const loginTab = document.getElementById('login-tab');
        const registerTab = document.getElementById('register-tab');
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const loadingScreen = document.getElementById('loading-screen');
        const profileModal = document.getElementById('profile-modal');
        
        // User state (simulação)
        let isLoggedIn = false;
        let currentUser = null;
        
        // Check if user is logged in
        const checkLoginState = async () => {
            try {
                showLoading();
                console.log('Verificando estado do login...');
                
                const supabase = await initSupabase();
                const { data: { session }, error } = await supabase.auth.getSession();
                
                if (error) {
                    console.error('Erro ao verificar sessão:', error);
                    throw error;
                }

                if (!session) {
                    console.log('Nenhuma sessão ativa encontrada');
                    isLoggedIn = false;
                    currentUser = null;
                    updateUIForLoggedInUser();
                    hideLoading();
                    return;
                }

                console.log('Sessão ativa encontrada:', session);
                
                // Obter dados atualizados do usuário
                const { data: { user }, error: userError } = await supabase.auth.getUser();
                
                if (userError) {
                    console.error('Erro ao obter dados do usuário:', userError);
                    throw userError;
                }

                if (!user) {
                    throw new Error('Dados do usuário não encontrados');
                }

                // Atualizar estado do usuário
                currentUser = {
                    id: user.id,
                    name: user.user_metadata?.name || 'Usuário',
                    email: user.email
                };
                isLoggedIn = true;

                // Verificar se já existe uma sessão registrada
                const currentSessionId = localStorage.getItem('currentSessionId');
                if (!currentSessionId) {
                    await registerUserSession();
                }

                console.log('Login restaurado com sucesso:', currentUser);
                updateUIForLoggedInUser();
                
            } catch (error) {
                console.error('Erro ao verificar login:', error);
                isLoggedIn = false;
                currentUser = null;
                updateUIForLoggedInUser();
            } finally {
                hideLoading();
            }
        };

        // Adicionar listener para mudanças de autenticação
        const setupAuthListener = async () => {
            try {
                const supabase = await initSupabase();
                
                supabase.auth.onAuthStateChange((event, session) => {
                    console.log('Mudança no estado de autenticação:', event, session);
                    
                    if (event === 'SIGNED_IN') {
                        checkLoginState();
                    } else if (event === 'SIGNED_OUT') {
                        isLoggedIn = false;
                        currentUser = null;
                        localStorage.removeItem('currentSessionId');
                        updateUIForLoggedInUser();
                    }
                });
            } catch (error) {
                console.error('Erro ao configurar listener de autenticação:', error);
            }
        };

        // Inicializar verificação de autenticação quando a página carregar
        document.addEventListener('DOMContentLoaded', async () => {
            await checkLoginState();
            await setupAuthListener();
            
            // ... resto do código existente do DOMContentLoaded ...
        });
        
        // Update UI elements based on login state
        const updateUIForLoggedInUser = () => {
            if (isLoggedIn && currentUser) {
                // Update account toggle button to show profile icon
                const buttonContent = `
                    <div class="w-6 h-6 bg-white text-black rounded-full flex items-center justify-center font-bold">
                        ${currentUser.name.charAt(0)}
                    </div>
                `;
                accountToggle.innerHTML = buttonContent;
                if (mobileAccountToggle) {
                    mobileAccountToggle.innerHTML = `
                        ${buttonContent}
                        <span class="text-xs mt-1">Perfil</span>
                    `;
                }
                
                // Update profile modal info
                document.querySelector('.user-initial').textContent = currentUser.name.charAt(0);
                document.querySelector('.user-name').textContent = currentUser.name;
                document.querySelector('.user-email').textContent = currentUser.email;
            } else {
                // Reset to default account icon
                accountToggle.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                `;
                if (mobileAccountToggle) {
                    mobileAccountToggle.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-xs mt-1">Conta</span>
                    `;
                }
            }
        };
        
        // Show loading screen
        const showLoading = () => {
            loadingScreen.classList.remove('hidden');
        };
        
        // Hide loading screen
        const hideLoading = () => {
            loadingScreen.classList.add('hidden');
        };
        
        // Handle login logic
        const handleLogin = async (email, password) => {
            showLoading();
            
            const maxRetries = 3;
            let retryCount = 0;
            let lastError = null;

            while (retryCount < maxRetries) {
                try {
                    console.log(`Tentativa ${retryCount + 1} de ${maxRetries} para fazer login`);
                    
                    const supabase = await initSupabase();
                    if (!supabase) {
                        throw new Error('Cliente Supabase não inicializado');
                    }

                    // Adicionar um pequeno delay antes de cada tentativa (exceto a primeira)
                    if (retryCount > 0) {
                        await new Promise(resolve => setTimeout(resolve, Math.pow(2, retryCount) * 1000));
                    }

                    console.log('Tentando login no Supabase...');
                    const { data, error } = await supabase.auth.signInWithPassword({
                        email: email,
                        password: password
                    });
                    
                    console.log('Resposta do login:', { data, error });
                    
                    if (error) {
                        // Log detalhado do erro
                        console.error('Erro detalhado do login:', {
                            message: error.message,
                            status: error.status,
                            details: error.details
                        });
                        
                        // Se o erro for 503 ou a mensagem estiver vazia, vamos tentar novamente
                        if (error.status === 503 || error.message === '{}') {
                            throw new Error('Serviço temporariamente indisponível');
                        }
                        
                        // Para outros tipos de erro, mostrar mensagem apropriada
                        let errorMessage = 'Erro ao fazer login';
                        if (error.message?.includes('Invalid login credentials')) {
                            errorMessage = 'Email ou senha incorretos';
                        } else if (error.message?.includes('Email not confirmed')) {
                            errorMessage = 'Por favor, confirme seu email antes de fazer login';
                        }
                        
                        hideLoading();
                        showNotification(errorMessage, 'error');
                        return;
                    }
                    
                    if (!data?.user) {
                        throw new Error('Dados do usuário não encontrados na resposta');
                    }

                    // Login bem-sucedido
                    console.log('Login bem-sucedido, configurando usuário...');
                    currentUser = {
                        id: data.user.id,
                        name: data.user.user_metadata?.name || 'Usuário',
                        email: data.user.email
                    };
                    isLoggedIn = true;
                    
                    // Registrar a sessão do usuário
                    console.log('Registrando sessão do usuário...');
                    await registerUserSession();
                    
                    // Atualizar UI
                    console.log('Atualizando interface...');
                    updateUIForLoggedInUser();
                    hideLoading();
                    
                    // Mostrar mensagem de sucesso
                    showNotification('Login realizado com sucesso!', 'success');
                    
                    // Fechar o modal de login se existir
                    const authModal = document.getElementById('auth-modal');
                    if (authModal) {
                        closeModal(authModal);
                    }
                    
                    return; // Sair do loop se bem sucedido
                    
                } catch (error) {
                    console.error(`Erro na tentativa ${retryCount + 1}:`, error);
                    lastError = error;
                    retryCount++;
                    
                    // Se ainda houver tentativas restantes, continuar
                    if (retryCount < maxRetries) {
                        console.log(`Tentando novamente em ${Math.pow(2, retryCount)} segundos...`);
                        continue;
                    }
                    
                    // Se chegou aqui, todas as tentativas falharam
                    console.error('Todas as tentativas de login falharam:', lastError);
                    hideLoading();
                    showNotification('Serviço temporariamente indisponível. Por favor, tente novamente em alguns minutos.', 'error');
                }
            }
        };
        
        // Handle registration logic
        const handleRegistration = async (name, email, password, confirmPassword) => {
            // Validar formulário
            if (password !== confirmPassword) {
                showNotification('As senhas não correspondem.', 'error');
                return;
            }
            
            if (password.length < 6) {
                showNotification('A senha deve ter pelo menos 6 caracteres.', 'error');
                return;
            }
            
            showLoading();
            
            const maxRetries = 3;
            let retryCount = 0;
            let lastError = null;

            while (retryCount < maxRetries) {
                try {
                    console.log(`Tentativa ${retryCount + 1} de ${maxRetries} para registrar usuário`);
                    
                    const supabase = await initSupabase();
                    if (!supabase) {
                        throw new Error('Cliente Supabase não inicializado');
                    }

                    const { data, error } = await supabase.auth.signUp({
                    email,
                        password,
                        options: {
                            data: { name }
                        }
                    });
                    
                    if (error) {
                        console.error('Erro detalhado do registro:', {
                            message: error.message,
                            status: error.status,
                            details: error.details
                        });
                        
                        // Se for um erro que não deve ser retentado
                        if (error.message?.includes('User already registered') || 
                            error.message?.includes('Invalid email') ||
                            error.message?.includes('Password too short')) {
                            hideLoading();
                            showNotification(error.message, 'error');
                            return;
                        }
                        
                        throw error; // Lançar erro para tentar novamente
                    }
                    
                    // Registro bem-sucedido
                currentUser = {
                        id: data.user.id,
                        name: data.user.user_metadata?.name || name,
                        email: data.user.email
                };
                isLoggedIn = true;
                
                    // Registrar a sessão do usuário
                    await registerUserSession();
                    
                    // Atualizar UI
                updateUIForLoggedInUser();
                hideLoading();
                
                    // Mostrar mensagem de sucesso com informações sobre verificação de email
                    if (data.user.identities && data.user.identities.length > 0 
                        && data.user.identities[0].identity_data
                        && data.user.identities[0].identity_data.email_verified === false) {
                        
                        // Criar modal de confirmação de email
                        const emailConfirmationModal = document.createElement('div');
                        emailConfirmationModal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                        emailConfirmationModal.innerHTML = `
                            <div class="bg-zinc-900 p-8 rounded-lg max-w-md w-full mx-4 relative">
                                <div class="text-center mb-6">
                                    <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"></path>
                                    </svg>
                                    <h2 class="text-2xl font-bold mb-2">Verifique seu Email</h2>
                                </div>
                                
                                <div class="space-y-4">
                                    <p class="text-gray-300">
                                        Enviamos um link de confirmação para: <br/>
                                        <span class="font-medium text-white">${email}</span>
                                    </p>
                                    
                                    <p class="text-gray-300">
                                        Por favor, verifique sua caixa de entrada e clique no link de confirmação para ativar sua conta.
                                    </p>

                                    <div class="bg-zinc-800 p-4 rounded-lg text-sm">
                                        <p class="font-medium text-white mb-2">Importante:</p>
                                        <p class="text-gray-300">O email será enviado por <span class="text-white font-medium">noreply@mail.app.supabase.io</span></p>
                                        <p class="text-gray-300 text-sm mt-1">Assunto: "Confirme seu email para o Wide Style"</p>
                                <div class="bg-zinc-800 p-4 rounded-lg text-sm">
                                    <p class="font-medium text-white mb-2">Importante:</p>
                                    <p class="text-gray-300">O email será enviado por <span class="text-white font-medium">noreply@mail.app.supabase.io</span></p>
                                    <p class="text-gray-300 text-sm mt-1">Assunto: "Confirme seu email para o Wide Style"</p>
                                </div>
                                
                                <div class="bg-zinc-800 p-4 rounded-lg text-sm text-gray-300">
                                    <p class="font-medium text-white mb-2">Não recebeu o email?</p>
                                    <ul class="list-disc list-inside space-y-2">
                                        <li>Verifique sua pasta de spam/lixo eletrônico</li>
                                        <li>Procure por emails do remetente "Supabase"</li>
                                        <li>Aguarde alguns minutos e verifique novamente</li>
                                        <li>Certifique-se de que o email está correto</li>
                                    </ul>
                                </div>
                                
                                <div class="mt-6 flex justify-end">
                                    <button class="bg-white text-black px-6 py-2 rounded hover:bg-gray-200 transition-colors" onclick="this.closest('.fixed').remove()">
                                        Entendi
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(emailConfirmationModal);
                    
                    // Fechar modal quando clicar fora
                    emailConfirmationModal.addEventListener('click', (e) => {
                        if (e.target === emailConfirmationModal) {
                            emailConfirmationModal.remove();
                        }
                    });
                    
                    showNotification('Conta criada! Verifique seu email para ativar.', 'success');
                } else {
                showNotification('Conta criada com sucesso!', 'success');
                }
                
                return; // Sair do loop se bem sucedido
                
            } catch (error) {
                console.error(`Erro na tentativa ${retryCount + 1}:`, error);
                lastError = error;
                retryCount++;
                
                if (retryCount < maxRetries) {
                    // Esperar um tempo antes de tentar novamente (exponential backoff)
                    await new Promise(resolve => setTimeout(resolve, Math.pow(2, retryCount) * 1000));
                }
            }
        }
        
        // Se chegou aqui, todas as tentativas falharam
        console.error('Todas as tentativas de registro falharam:', lastError);
        hideLoading();
        showNotification('Não foi possível completar o registro. Por favor, tente novamente mais tarde.', 'error');
        };
        
        // Handle logout
    const handleLogout = async () => {
            showLoading();
            
        try {
            const supabase = await initSupabase();
            
            // Encerrar a sessão atual no banco de dados
            const currentSessionId = localStorage.getItem('currentSessionId');
            if (currentSessionId) {
                await terminateSession(currentSessionId);
            }
            
            // Logout no Supabase Auth
            const { error } = await supabase.auth.signOut();
            
            if (error) {
                hideLoading();
                showNotification(error.message || 'Erro ao fazer logout', 'error');
                return;
            }
            
            // Logout bem-sucedido
                currentUser = null;
                isLoggedIn = false;
                
            // Limpar dados de sessão
            localStorage.removeItem('currentSessionId');
            
            // Atualizar UI
                updateUIForLoggedInUser();
                hideLoading();
                
            // Fechar modal
            if (profileModal) {
                closeModal(profileModal);
            }
                
            // Mostrar mensagem de sucesso
                showNotification('Logout realizado com sucesso!', 'success');
        } catch (error) {
            console.error('Erro ao fazer logout:', error);
            hideLoading();
            showNotification('Ocorreu um erro ao processar o logout', 'error');
        }
        };
        
        // Toggle theme
        const toggleTheme = () => {
            const htmlElement = document.documentElement;
            const isDarkMode = htmlElement.classList.contains('dark');
            
            // Toggle dark mode class on html element
            if (isDarkMode) {
                htmlElement.classList.remove('dark');
                // Update icon to moon (dark mode icon)
                const themeIcon = document.querySelector('#toggle-theme svg');
                if (themeIcon) {
                    themeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />`;
                }
                localStorage.setItem('theme', 'light');
            } else {
                htmlElement.classList.add('dark');
                // Update icon to sun (light mode icon)
                const themeIcon = document.querySelector('#toggle-theme svg');
                if (themeIcon) {
                    themeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />`;
                }
                localStorage.setItem('theme', 'dark');
            }
            
            // Dispatch theme changed event
            document.dispatchEvent(new CustomEvent('themeChanged', { 
                detail: { theme: isDarkMode ? 'light' : 'dark' } 
            }));
        };
        
        // Check theme preference
        const checkThemePreference = () => {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const htmlElement = document.documentElement;
            
            // Apply saved theme
            if (savedTheme === 'dark') {
                htmlElement.classList.add('dark');
                // Update icon to sun (light mode icon)
                const themeIcon = document.querySelector('#toggle-theme svg');
                if (themeIcon) {
                    themeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />`;
                }
            } else {
                htmlElement.classList.remove('dark');
                // Update icon to moon (dark mode icon)
                const themeIcon = document.querySelector('#toggle-theme svg');
                if (themeIcon) {
                    themeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />`;
                }
            }
            
            // Dispatch theme changed event
            document.dispatchEvent(new CustomEvent('themeChanged', { 
                detail: { theme: savedTheme } 
            }));
        };
        
        // Open account or profile modal based on login status
        const openAccountOrProfile = () => {
            if (isLoggedIn) {
                openModal(profileModal);
            } else {
                openModal(accountModal);
            }
        };
        
        // Open modal when account buttons are clicked
        accountToggle.addEventListener('click', function() {
            openAccountOrProfile();
        });
        
        mobileAccountToggle.addEventListener('click', function(e) {
            e.preventDefault();
            openAccountOrProfile();
        });
        
        // Close modal when close buttons are clicked
        closeModalButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.full-screen-modal');
                closeModal(modal);
            });
        });
        
        // Close modal when clicking outside the modal content
        document.querySelectorAll('.full-screen-modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this);
                }
            });
        });
        
        // Tab switching functionality
        loginTab.addEventListener('click', function() {
            loginTab.classList.add('border-white');
            loginTab.classList.remove('text-gray-400');
            registerTab.classList.remove('border-white');
            registerTab.classList.add('text-gray-400');
            loginForm.classList.remove('hidden');
            registerForm.classList.add('hidden');
        });
        
        registerTab.addEventListener('click', function() {
            registerTab.classList.add('border-white');
            registerTab.classList.remove('text-gray-400');
            loginTab.classList.remove('border-white');
            loginTab.classList.add('text-gray-400');
            registerForm.classList.remove('hidden');
            loginForm.classList.add('hidden');
        });
        
        // Form submission handling
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            
            // Close the account modal
            closeModal(accountModal);
            
            // Handle login
            handleLogin(email, password);
        });
        
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('register-name').value;
            const email = document.getElementById('register-email').value;
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('register-confirm-password').value;
            
            // Close the account modal
            closeModal(accountModal);
            
            // Handle registration
            handleRegistration(name, email, password, confirmPassword);
        });
        
        // Profile functionalities
        document.getElementById('profile-cart').addEventListener('click', function(e) {
            e.preventDefault();
            closeModal(profileModal);
            openModal(cartModal);
        });
        
        document.getElementById('profile-favorites').addEventListener('click', function(e) {
            e.preventDefault();
            showNotification('Recurso de Favoritos em desenvolvimento.', 'success');
        });
        
        document.getElementById('profile-orders').addEventListener('click', function(e) {
            e.preventDefault();
            showNotification('Recurso de Rastreio em desenvolvimento.', 'success');
        });
        
        document.getElementById('toggle-theme').addEventListener('click', toggleTheme);
        
        document.getElementById('logout-button').addEventListener('click', handleLogout);
        
        // Initialize auth state
        checkLoginState();
        
        // Initialize theme preference
        checkThemePreference();

        // Auth Modal Functions
        function openAuthModal() {
            const modal = document.getElementById('auth-modal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeAuthModal() {
            const modal = document.getElementById('auth-modal');
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Tab Switching
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active state from all tabs
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('border-theme-primary', 'text-theme-text');
                    btn.classList.add('opacity-75');
                });

                // Add active state to clicked tab
                button.classList.remove('opacity-75');
                button.classList.add('border-theme-primary', 'text-theme-text');

                // Show/hide forms
                const tabName = button.getAttribute('data-tab');
                document.getElementById('login-form').classList.toggle('hidden', tabName !== 'login');
                document.getElementById('register-form').classList.toggle('hidden', tabName !== 'register');
            });
        });

        // Close modal when clicking outside
        document.getElementById('auth-modal').addEventListener('click', (e) => {
            if (e.target.id === 'auth-modal') {
                closeAuthModal();
            }
        });

        // Close modal with close button
        document.getElementById('close-auth-modal').addEventListener('click', closeAuthModal);

        // Form Submissions
        document.getElementById('login-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            const rememberMe = document.getElementById('remember-me').checked;

            // Here you would typically make an API call to your backend
            console.log('Login attempt:', { email, password, rememberMe });
            
            // Show success message
            showNotification('Login successful!', 'success');
            closeAuthModal();
        });

        document.getElementById('register-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('register-name').value;
            const email = document.getElementById('register-email').value;
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('register-confirm-password').value;

            if (password !== confirmPassword) {
                showNotification('Passwords do not match!', 'error');
                return;
            }

            // Here you would typically make an API call to your backend
            console.log('Registration attempt:', { name, email, password });
            
            // Show success message
            showNotification('Registration successful!', 'success');
            closeAuthModal();
        });

        // Social Login Buttons
        document.querySelectorAll('.social-button').forEach(button => {
            button.addEventListener('click', () => {
                const provider = button.querySelector('span').textContent.toLowerCase();
                console.log(`${provider} login clicked`);
                // Here you would typically implement OAuth login
            });
        });

        // Add click event to account button
        document.getElementById('account-toggle').addEventListener('click', openAuthModal);

        // Favorites functionality
        let favorites = JSON.parse(localStorage.getItem('favorites')) || [];

        const toggleFavorite = (productId) => {
            const index = favorites.indexOf(productId);
            
            if (index === -1) {
                // Add to favorites
                favorites.push(productId);
                showNotification('Produto adicionado aos favoritos!');
            } else {
                // Remove from favorites
                favorites.splice(index, 1);
                showNotification('Produto removido dos favoritos!');
            }
            
            // Save to localStorage
            localStorage.setItem('favorites', JSON.stringify(favorites));
            
            // Update favorites button appearance
            updateFavoriteButtons();
            
            // Update favorites modal if open
            if (favoritesModal.classList.contains('active')) {
                updateFavoritesDisplay();
            }
        };

        const updateFavoriteButtons = () => {
            document.querySelectorAll('.add-to-favorites').forEach(button => {
                const productId = parseInt(button.getAttribute('data-product'));
                
                if (favorites.includes(productId)) {
                    // Product is favorited - change to filled heart
                    button.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    `;
                    button.classList.add('favorited');
                } else {
                    // Product is not favorited - use outline heart
                    button.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    `;
                    button.classList.remove('favorited');
                }
            });
        };

        const updateFavoritesDisplay = () => {
            // Clear grid
            favoritesGrid.innerHTML = '';
            
            if (favorites.length === 0) {
                // Show empty message
                emptyFavoritesMessage.classList.remove('hidden');
                return;
            }
            
            // Hide empty message
            emptyFavoritesMessage.classList.add('hidden');
            
            // Add each favorite product to the grid
            favorites.forEach(productId => {
                const product = products.find(p => p.id === productId);
                if (!product) return;
                
                const productElement = document.createElement('div');
                productElement.className = 'product-card group';
                productElement.innerHTML = `
                    <div class="relative aspect-[3/4] mb-3 bg-gray-200 dark:bg-zinc-900 overflow-hidden rounded-lg">
                        <img src="${product.images.front}" alt="${product.name}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300"></div>
                        <button class="absolute top-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center remove-from-favorites" data-product="${product.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 left-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center add-to-cart-from-favorites" data-product="${product.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <button class="absolute bottom-3 right-3 bg-white text-black w-9 h-9 rounded-full flex items-center justify-center quick-view-from-favorites" data-product="${product.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">${product.category}</p>
                        <h3 class="text-sm md:text-base font-medium mt-1">${product.name}</h3>
                        <p class="text-black dark:text-white mt-1 text-sm md:text-base">R$ ${product.price.toFixed(2).replace('.', ',')}</p>
                    </div>
                `;
                
                favoritesGrid.appendChild(productElement);
            });
            
            // Add event listeners to new buttons
            attachFavoritesEventListeners();
        };

        const attachFavoritesEventListeners = () => {
            // Remove from favorites
            document.querySelectorAll('.remove-from-favorites').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = parseInt(this.getAttribute('data-product'));
                    toggleFavorite(productId);
                });
            });
            
            // Add to cart from favorites
            document.querySelectorAll('.add-to-cart-from-favorites').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = parseInt(this.getAttribute('data-product'));
                    addToCart(productId);
                });
            });
            
            // Quick view from favorites
            document.querySelectorAll('.quick-view-from-favorites').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = parseInt(this.getAttribute('data-product'));
                    openProductDetail(productId);
                });
            });
        };

        // Add profile links functionality
        document.getElementById('profile-favorites').addEventListener('click', function(e) {
            e.preventDefault();
            closeModal(document.getElementById('profile-modal'));
            openModal(favoritesModal);
            updateFavoritesDisplay();
        });

        // Initialize
        updateCartDisplay();
        updateFavoriteButtons();

    // Inicializar todos os formulários e listeners relacionados à autenticação
    const initAuthForms = () => {
        // Formulário de recuperação de senha (forgot-password)
        const forgotPasswordForm = document.getElementById('forgot-password-form');
        const forgotPasswordModal = document.getElementById('forgot-password-modal');
        const openForgotPasswordBtn = document.getElementById('open-forgot-password');
        
        if (forgotPasswordForm) {
            forgotPasswordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const email = document.getElementById('forgot-password-email').value;
                
                showLoading();
                
                const result = await requestPasswordReset(email);
                
                hideLoading();
                
                showNotification(result.message || 'Instruções de recuperação enviadas para seu email.', 'success');
                
                // Fechar modal
                if (forgotPasswordModal) {
                    closeModal(forgotPasswordModal);
                }
            });
        }
        
        if (openForgotPasswordBtn && forgotPasswordModal) {
            openForgotPasswordBtn.addEventListener('click', function() {
                openModal(forgotPasswordModal);
            });
        }
        
        // Formulário de redefinição de senha (reset-password)
        const resetPasswordForm = document.getElementById('reset-password-form');
        
        if (resetPasswordForm) {
            resetPasswordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                
                if (newPassword !== confirmPassword) {
                    showNotification('As senhas não coincidem', 'error');
                    return;
                }
                
                if (newPassword.length < 6) {
                    showNotification('A senha deve ter pelo menos 6 caracteres', 'error');
                    return;
                }
                
                // Obter token da URL
                const urlParams = new URLSearchParams(window.location.search);
                const token = urlParams.get('token');
                
                if (!token) {
                    showNotification('Token de redefinição não encontrado', 'error');
                    return;
                }
                
                showLoading();
                
                const result = await resetPasswordWithToken(token, newPassword);
                
                hideLoading();
                
                if (result.success) {
                    showNotification('Senha redefinida com sucesso!', 'success');
                    
                    // Redirecionar para página de login após 2 segundos
                    setTimeout(() => {
                        window.location.href = 'index.html';
                    }, 2000);
                } else {
                    showNotification(result.error || 'Erro ao redefinir senha', 'error');
                }
            });
        }
        
        // Formulário de alteração de senha (change-password)
        const changePasswordForm = document.getElementById('change-password-form');
        const changePasswordModal = document.getElementById('change-password-modal');
        const openChangePasswordBtn = document.getElementById('open-change-password');
        
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const currentPassword = document.getElementById('current-password').value;
                const newPassword = document.getElementById('change-new-password').value;
                const confirmPassword = document.getElementById('change-confirm-password').value;
                
                if (newPassword !== confirmPassword) {
                    showNotification('As novas senhas não coincidem', 'error');
                    return;
                }
                
                if (newPassword.length < 6) {
                    showNotification('A nova senha deve ter pelo menos 6 caracteres', 'error');
                    return;
                }
                
                showLoading();
                
                const result = await changePassword(currentPassword, newPassword);
                
                hideLoading();
                
                if (result.success) {
                    showNotification('Senha alterada com sucesso!', 'success');
                    
                    // Fechar modal
                    if (changePasswordModal) {
                        closeModal(changePasswordModal);
                    }
                    
                    // Limpar formulário
                    changePasswordForm.reset();
                } else {
                    showNotification(result.error || 'Erro ao alterar senha', 'error');
                }
            });
        }
        
        if (openChangePasswordBtn && changePasswordModal) {
            openChangePasswordBtn.addEventListener('click', function() {
                if (isLoggedIn) {
                    openModal(changePasswordModal);
                } else {
                    showNotification('Você precisa estar logado para alterar sua senha', 'error');
                }
            });
        }
        
        // Gerenciamento de sessões do usuário
        const sessionsModal = document.getElementById('sessions-modal');
        const openSessionsBtn = document.getElementById('open-sessions');
        const sessionsContainer = document.getElementById('sessions-container');
        
        const loadUserSessions = async () => {
            if (!sessionsContainer) return;
            
            sessionsContainer.innerHTML = '<p class="text-center">Carregando suas sessões...</p>';
            
            const result = await getUserSessions();
            
            if (result.success && result.sessions) {
                if (result.sessions.length === 0) {
                    sessionsContainer.innerHTML = '<p class="text-center">Nenhuma sessão ativa encontrada.</p>';
                    return;
                }
                
                sessionsContainer.innerHTML = '';
                
                const currentSessionId = localStorage.getItem('currentSessionId');
                
                result.sessions.forEach(session => {
                    const isCurrentSession = session.id === currentSessionId;
                    
                    const sessionElement = document.createElement('div');
                    sessionElement.className = 'border-b border-gray-700 py-3';
                    sessionElement.innerHTML = `
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium">${isCurrentSession ? 'Esta sessão' : 'Outra sessão'}</p>
                                <p class="text-sm text-gray-400">${session.ip_address || 'Endereço IP desconhecido'}</p>
                                <p class="text-xs text-gray-500">Último acesso: ${new Date(session.last_active).toLocaleString()}</p>
                            </div>
                            ${!isCurrentSession ? `
                                <button class="bg-red-600 text-white px-3 py-1 rounded text-sm terminate-session" data-session-id="${session.id}">
                                    Encerrar
                                </button>
                            ` : ''}
                        </div>
                    `;
                    
                    sessionsContainer.appendChild(sessionElement);
                });
                
                // Adicionar botão para encerrar todas as outras sessões
                if (result.sessions.length > 1) {
                    const terminateAllBtn = document.createElement('div');
                    terminateAllBtn.className = 'mt-4 text-center';
                    terminateAllBtn.innerHTML = `
                        <button id="terminate-all-sessions" class="bg-red-600 text-white px-4 py-2 rounded">
                            Encerrar todas as outras sessões
                        </button>
                    `;
                    
                    sessionsContainer.appendChild(terminateAllBtn);
                    
                    document.getElementById('terminate-all-sessions').addEventListener('click', async function() {
                        showLoading();
                        
                        const result = await terminateOtherSessions();
                        
                        hideLoading();
                        
                        if (result.success) {
                            showNotification(`${result.count} sessões encerradas com sucesso!`, 'success');
                            loadUserSessions(); // Recarregar lista
                        } else {
                            showNotification(result.error || 'Erro ao encerrar sessões', 'error');
                        }
                    });
                }
                
                // Adicionar event listeners para os botões de encerrar sessão
                document.querySelectorAll('.terminate-session').forEach(button => {
                    button.addEventListener('click', async function() {
                        const sessionId = this.getAttribute('data-session-id');
                        
                        showLoading();
                        
                        const result = await terminateSession(sessionId);
                        
                        hideLoading();
                        
                        if (result.success) {
                            showNotification('Sessão encerrada com sucesso!', 'success');
                            loadUserSessions(); // Recarregar lista
                        } else {
                            showNotification(result.error || 'Erro ao encerrar sessão', 'error');
                        }
                    });
                });
            } else {
                sessionsContainer.innerHTML = `<p class="text-center text-red-500">Erro ao carregar sessões: ${result.error || 'Erro desconhecido'}</p>`;
            }
        };
        
        if (openSessionsBtn && sessionsModal) {
            openSessionsBtn.addEventListener('click', function() {
                if (isLoggedIn) {
                    openModal(sessionsModal);
                    loadUserSessions();
                } else {
                    showNotification('Você precisa estar logado para gerenciar suas sessões', 'error');
                }
            });
        }
    };
    
    // Inicializar autenticação e formulários
    checkLoginState();
    initAuthForms();

    // Forgot Password Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const forgotPasswordLink = document.getElementById('forgot-password-link');
        
        if (forgotPasswordLink) {
            forgotPasswordLink.addEventListener('click', function(e) {
                e.preventDefault();
                openForgotPasswordModal();
            });
        }

        // Setup close buttons for forgot password modal
        const forgotPasswordCloseButtons = document.querySelectorAll('.forgot-password-modal .close-modal');
        forgotPasswordCloseButtons.forEach(button => {
            button.addEventListener('click', closeForgotPasswordModal);
        });

        // Setup forgot password form submission
        const forgotPasswordForm = document.getElementById('forgot-password-form');
        if (forgotPasswordForm) {
            forgotPasswordForm.addEventListener('submit', handleForgotPassword);
        }
    });

    function openForgotPasswordModal() {
        const modal = document.querySelector('.forgot-password-modal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            
            // Focus on email input
            setTimeout(() => {
                const emailInput = document.getElementById('forgot-password-email');
                if (emailInput) emailInput.focus();
            }, 100);
        }
    }

    function closeForgotPasswordModal() {
        const modal = document.querySelector('.forgot-password-modal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }

    function handleForgotPassword(e) {
        e.preventDefault();
        
        const emailInput = document.getElementById('forgot-password-email');
        const email = emailInput.value.trim();
        
        if (!email) {
            showNotification('Por favor, digite seu e-mail', 'error');
            return;
        }
        
        // Show loading
        showLoading();
        
        // Usar a função real de requestPasswordReset com Supabase
        (async () => {
            try {
                const result = await requestPasswordReset(email);
                
                if (result.success) {
                    showNotification(result.message || 'Link de recuperação enviado para seu e-mail', 'success');
                    closeForgotPasswordModal();
                    emailInput.value = '';
                } else {
                    showNotification(result.error || 'Erro ao enviar link de recuperação', 'error');
                }
            } catch (error) {
                console.error('Erro ao solicitar recuperação de senha:', error);
                showNotification('Ocorreu um erro ao processar sua solicitação', 'error');
            } finally {
                hideLoading();
            }
        })();
    }

    // Função para alternar visibilidade da senha
    function setupPasswordToggles() {
        const toggleButtons = document.querySelectorAll('.toggle-password');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const input = this.parentElement.querySelector('input');
                const showIcon = this.querySelector('.show-password');
                const hideIcon = this.querySelector('.hide-password');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    showIcon.classList.add('hidden');
                    hideIcon.classList.remove('hidden');
                } else {
                    input.type = 'password';
                    showIcon.classList.remove('hidden');
                    hideIcon.classList.add('hidden');
                }
            });
        });
    }

    // Adicionar ao DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        setupPasswordToggles();
        // ... resto do código existente ...
    });
});