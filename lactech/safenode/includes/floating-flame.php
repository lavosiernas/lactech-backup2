<?php

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    return; 
}

$userId = $_SESSION['safenode_user_id'] ?? null;
$siteId = $_SESSION['view_site_id'] ?? 0;

if (!$userId) {
    return;
}

require_once __DIR__ . '/ProtectionStreak.php';
$streakManager = new ProtectionStreak();
$protectionStreak = $streakManager->getStreak($userId, $siteId);

$isEnabled = $protectionStreak && isset($protectionStreak['enabled']) && $protectionStreak['enabled'];
$isActive = $protectionStreak && isset($protectionStreak['is_active']) && $protectionStreak['is_active'];

// Só mostrar se estiver habilitado E ativo
$shouldShow = $isEnabled && $isActive;

?>

<?php if ($shouldShow): ?>
<div id="floating-flame-container" 
     x-data="floatingFlameData(<?php echo $protectionStreak['current_streak'] ?? 0; ?>, <?php echo $protectionStreak['longest_streak'] ?? 0; ?>, <?php echo isset($protectionStreak['last_protected_date']) && $protectionStreak['last_protected_date'] ? "'" . date('d/m/Y', strtotime($protectionStreak['last_protected_date'])) . "'" : 'null'; ?>)"
     x-show="visible"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     :style="visible ? '' : 'display: none !important;'"
     x-cloak
     style="display: none;">
    <div 
        id="floating-flame-button"
        @mousedown="startDrag($event)"
        @touchstart="startDrag($event)"
        class="fixed z-50 cursor-move transition-all duration-200"
        :style="`left: ${position.x}px; top: ${position.y}px; transform: translate(-50%, -50%);`"
        :class="isDragging ? 'scale-125' : 'hover:scale-110'">
        
        <div class="relative">
            <button 
                @click="openModal()"
                class="relative flex items-center justify-center transition-all duration-200"
                :class="isDragging ? 'cursor-grabbing' : 'cursor-pointer'">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="flame-effect"></div>
                </div>
                <i data-lucide="flame" class="w-12 h-12 text-orange-500 relative z-10 flame-icon"></i>
            </button>
            <div x-show="showHideOption" 
                 x-transition
                 class="absolute -bottom-14 left-1/2 -translate-x-1/2 bg-dark-800/95 backdrop-blur-sm border border-white/10 rounded-lg px-3 py-2 shadow-xl whitespace-nowrap z-20">
                <button 
                    @click.stop="hideFlame()"
                    class="text-xs text-zinc-300 hover:text-white flex items-center gap-2 transition-colors">
                    <i data-lucide="eye-off" class="w-3 h-3"></i>
                    Ocultar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Full Screen como Página -->
    <div 
        x-show="modalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed inset-0 z-[100] bg-dark-950 overflow-y-auto"
        style="display: none;">
        
        <!-- Header fixo -->
        <header class="sticky top-0 z-10 bg-dark-900/95 backdrop-blur-xl border-b border-white/5 px-8 py-6">
            <div class="flex items-center justify-between max-w-7xl mx-auto">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center">
                        <i data-lucide="flame" class="w-6 h-6 text-white animate-pulse"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Sequência de Proteção</h1>
                        <p class="text-sm text-zinc-400 mt-0.5">Acompanhe seus dias consecutivos de proteção</p>
                    </div>
                </div>
                <button 
                    @click="closeModal()"
                    class="w-10 h-10 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </header>
        
        <!-- Conteúdo -->
        <div class="max-w-7xl mx-auto px-8 py-8">
            <!-- Cards de Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Sequência Atual -->
                <div class="glass rounded-2xl p-8 border border-white/10">
                    <div class="text-xs text-zinc-500 mb-3 uppercase tracking-wider font-semibold">Sequência Atual</div>
                    <div class="text-6xl font-bold text-orange-400 mb-3">
                        <span x-text="currentStreak"></span>
                        <span class="text-3xl text-zinc-500"> dias</span>
                    </div>
                    <p class="text-sm text-zinc-400">Dias consecutivos protegidos</p>
                </div>
                
                <!-- Recorde -->
                <div class="glass rounded-2xl p-8 border border-white/10">
                    <div class="text-xs text-zinc-500 mb-3 uppercase tracking-wider font-semibold">Recorde</div>
                    <div class="text-6xl font-bold text-zinc-300 mb-3">
                        <span x-text="longestStreak"></span>
                        <span class="text-3xl text-zinc-500"> dias</span>
                    </div>
                    <p class="text-sm text-zinc-400">Maior sequência alcançada</p>
                </div>
            </div>
            
            <!-- Informações Adicionais -->
            <div class="glass rounded-2xl p-6 border border-white/10 mb-8">
                <h3 class="text-lg font-semibold text-white mb-4">Estatísticas</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between py-2 border-b border-white/5">
                        <span class="text-sm text-zinc-400">Última proteção registrada</span>
                        <span class="text-sm font-medium text-white" x-text="lastProtectedDate || 'N/A'"></span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-sm text-zinc-400">Status</span>
                        <span class="px-3 py-1 bg-green-500/20 border border-green-500/30 rounded-lg text-xs font-semibold text-green-400">ATIVO</span>
                    </div>
                </div>
            </div>
            
            <!-- Botão para Configurações -->
            <div class="flex justify-center">
                <a 
                    href="settings.php"
                    class="px-8 py-3 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-400 hover:to-red-500 text-white rounded-xl font-semibold transition-all duration-200 flex items-center gap-2 shadow-lg shadow-orange-500/20">
                    <i data-lucide="settings" class="w-4 h-4"></i>
                    Configurações
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function floatingFlameData(currentStreak, longestStreak, lastProtectedDate) {
    return {
        currentStreak: currentStreak || 0,
        longestStreak: longestStreak || 0,
        lastProtectedDate: lastProtectedDate || null,
        visible: true,
        modalOpen: false,
        isDragging: false,
        showHideOption: false,
        position: { x: 20, y: 20 },
        dragStart: { x: 0, y: 0 },
        
        init() {
            // Verificar se o streak foi desativado
            this.checkStreakStatus();
            
            // Carregar posição salva ou usar padrão (canto inferior direito)
            const savedPosition = localStorage.getItem('floatingFlamePosition');
            if (savedPosition) {
                try {
                    this.position = JSON.parse(savedPosition);
                } catch (e) {
                    console.error('Erro ao carregar posição:', e);
                    // Posição padrão: canto inferior direito
                    this.position = { 
                        x: window.innerWidth - 60, 
                        y: window.innerHeight - 60 
                    };
                }
            } else {
                // Posição padrão: canto inferior direito
                this.position = { 
                    x: window.innerWidth - 60, 
                    y: window.innerHeight - 60 
                };
            }
            
            // Ajustar posição se estiver fora da tela
            this.adjustPosition();
            
            // Carregar visibilidade
            const savedVisibility = localStorage.getItem('floatingFlameVisible');
            if (savedVisibility === 'false') {
                this.visible = false;
            }
            
            // Atualizar dados quando evento for disparado
            window.addEventListener('streak-updated', (e) => {
                if (e.detail) {
                    this.currentStreak = e.detail.current_streak || 0;
                    this.longestStreak = e.detail.longest_streak || 0;
                }
            });
            
            // Escutar quando streak é atualizado (ativado/desativado)
            window.addEventListener('streak-updated', (e) => {
                if (e.detail && e.detail.hasOwnProperty('enabled')) {
                    if (!e.detail.enabled) {
                        // Streak foi desativado, esconder foguinho imediatamente
                        this.visible = false;
                        this.hideFlame();
                    } else {
                        // Streak foi ativado, verificar se deve mostrar
                        const savedVisibility = localStorage.getItem('floatingFlameVisible');
                        if (savedVisibility !== 'false') {
                            this.visible = true;
                            this.showFlame();
                        }
                    }
                }
            });
            
            // Ajustar posição ao redimensionar
            window.addEventListener('resize', () => {
                this.adjustPosition();
            });
        },
        
        adjustPosition() {
            // Garantir que o botão está dentro da tela
            const maxX = window.innerWidth - 30;
            const maxY = window.innerHeight - 30;
            const minX = 30;
            const minY = 30;
            
            this.position.x = Math.max(minX, Math.min(this.position.x, maxX));
            this.position.y = Math.max(minY, Math.min(this.position.y, maxY));
        },
        
        startDrag(e) {
            e.preventDefault();
            this.isDragging = true;
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            
            // Salvar posição inicial do mouse/touch
            this.dragStart = {
                x: clientX,
                y: clientY
            };
            
            // Adicionar listeners
            const handleMove = this.handleDrag.bind(this);
            const handleEnd = this.stopDrag.bind(this);
            
            document.addEventListener('mousemove', handleMove, { passive: false });
            document.addEventListener('touchmove', handleMove, { passive: false });
            document.addEventListener('mouseup', handleEnd);
            document.addEventListener('touchend', handleEnd);
            
            // Guardar referências para remover depois
            this._handleMove = handleMove;
            this._handleEnd = handleEnd;
        },
        
        handleDrag(e) {
            if (!this.isDragging) return;
            
            e.preventDefault();
            
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            
            // Calcular nova posição (centro do botão)
            this.position.x = clientX;
            this.position.y = clientY;
            
            // Limitar dentro da tela (considerando o centro do botão)
            const buttonSize = 48; // Tamanho aproximado do botão
            const maxX = window.innerWidth - (buttonSize / 2);
            const maxY = window.innerHeight - (buttonSize / 2);
            const minX = buttonSize / 2;
            const minY = buttonSize / 2;
            
            this.position.x = Math.max(minX, Math.min(this.position.x, maxX));
            this.position.y = Math.max(minY, Math.min(this.position.y, maxY));
            
            // Mostrar opção de ocultar após arrastar um pouco
            const dragDistance = Math.abs(clientX - (this.dragStart.x + this.position.x)) + 
                                 Math.abs(clientY - (this.dragStart.y + this.position.y));
            this.showHideOption = dragDistance > 80;
        },
        
        stopDrag() {
            this.isDragging = false;
            this.showHideOption = false;
            
            // Salvar posição
            localStorage.setItem('floatingFlamePosition', JSON.stringify(this.position));
            
            // Remover listeners
            if (this._handleMove) {
                document.removeEventListener('mousemove', this._handleMove);
                document.removeEventListener('touchmove', this._handleMove);
            }
            if (this._handleEnd) {
                document.removeEventListener('mouseup', this._handleEnd);
                document.removeEventListener('touchend', this._handleEnd);
            }
        },
        
        hideFlame() {
            this.visible = false;
            localStorage.setItem('floatingFlameVisible', 'false');
            // Garantir que o elemento seja escondido completamente
            const container = document.getElementById('floating-flame-container');
            if (container) {
                container.style.display = 'none';
            }
        },
        
        showFlame() {
            this.visible = true;
            localStorage.setItem('floatingFlameVisible', 'true');
            // Garantir que o elemento seja mostrado
            const container = document.getElementById('floating-flame-container');
            if (container) {
                container.style.display = '';
            }
        },
        
        openModal() {
            if (!this.isDragging) {
                this.modalOpen = true;
                document.body.style.overflow = 'hidden';
            }
        },
        
        closeModal() {
            this.modalOpen = false;
            document.body.style.overflow = '';
        }
    };
}

// Expor função global para mostrar o foguinho
window.showFloatingFlame = function() {
    const container = document.getElementById('floating-flame-container');
    if (container && container.__x) {
        container.__x.$data.showFlame();
    }
};

// Recriar ícones quando modal abrir
document.addEventListener('alpine:init', () => {
    Alpine.effect(() => {
        if (typeof lucide !== 'undefined') {
            setTimeout(() => lucide.createIcons(), 100);
        }
    });
});
</script>

<style>
[x-cloak] { display: none !important; }
</style>

<?php endif; ?>

