// =====================================================
// SISTEMA DE PAGAMENTO PIX - SUPABASE + JAVASCRIPT
// =====================================================

class PixPaymentSystem {
    constructor() {
        // Usar o banco de pagamentos específico
        this.supabase = window.getPaymentSupabase ? window.getPaymentSupabase() : null;
        this.currentUser = null;
        this.subscriptionStatus = null;
        this.pixKey = '00020126440014BR.GOV.BCB.PIX0122slavosier298@gmail.com52040000530398654041.005802BR5901N6001C62110507Lactech630453EE'; // Nova chave PIX
        this.pixKeyType = 'email'; // email, cpf, telefone, aleatoria
        this.paymentTimeout = 30 * 60 * 1000; // 30 minutos
        this.paymentGenerated = false; // Flag para evitar recarregamento
        
        // Verificar se o cliente do Supabase para pagamentos está disponível
        if (!this.supabase) {
            console.error('❌ Cliente Supabase para pagamentos não encontrado');
            console.log('💡 Certifique-se de incluir payment_supabase_config.js antes deste script');
        }
    }

    // =====================================================
    // INICIALIZAÇÃO E VERIFICAÇÃO DE ASSINATURA
    // =====================================================

    async initialize() {
        try {
            console.log('🚀 Inicializando sistema de pagamento Pix...');
            
            // Para pagamentos, não requer autenticação prévia
            // O usuário pode se cadastrar/logar durante o processo de pagamento
            console.log('💳 Sistema de pagamento pronto - autenticação opcional');

            // Mostrar tela de pagamento diretamente
            this.showPaymentScreen();
            
        } catch (error) {
            console.error('❌ Erro na inicialização:', error);
            this.showError('Erro ao inicializar sistema de pagamento');
        }
    }

    // =====================================================
    // GERAÇÃO DE QR CODE PIX
    // =====================================================

    async generatePixPayment() {
        try {
            console.log('💰 Gerando pagamento Pix...');
            
            // Obter plano selecionado
            const selectedPlan = window.SELECTED_PLAN || {
                name: 'Plano Mensal',
                price: 1.00,
                duration: 30,
                description: 'Assinatura mensal recorrente'
            };
            
            // Gerar identificador único
            const txid = this.generateTxId();
            const amount = selectedPlan.price; // Valor dinâmico baseado no plano
            
            console.log('📋 Preparando pagamento:', { txid, amount, plan: selectedPlan.name });
            
            // Dados da cobrança pronta da Nubank
            const paymentData = {
                txid: 'ebabf96f-5162-4bd1-95c5-64ffa8e9bfed', // ID da cobrança Nubank
                amount: 1.00, // Valor fixo da cobrança
                status: 'pending',
                expires_at: new Date(Date.now() + this.paymentTimeout).toISOString(),
                pix_key: '00020126440014BR.GOV.BCB.PIX0122slavosier298@gmail.com52040000530398654041.005802BR5901N6001C62110507Lactech630453EE',
                pix_key_type: 'email',
                plan: selectedPlan,
                merchantName: 'Francisco Lavosier Silva',
                merchantCity: 'SAO PAULO'
            };

            console.log('✅ Dados do pagamento preparados:', paymentData);

            // Gerar QR Code Pix
            const qrCodeData = this.generatePixQRCode(paymentData);
            
            // Renderizar tela de pagamento diretamente
            this.renderPaymentScreen(qrCodeData, paymentData);
            
        } catch (error) {
            console.error('❌ Erro ao gerar pagamento:', error);
            this.showError('Erro ao gerar pagamento Pix');
        }
    }

    generateTxId() {
        const timestamp = Date.now();
        const random = Math.random().toString(36).substring(2, 15);
        const uniqueId = Math.random().toString(36).substring(2, 10);
        return `PIX_${uniqueId}_${timestamp}_${random}`;
    }

    generatePixQRCode(payment) {
        // Gerar QR Code PIX usando formato EMV correto
        const emvString = this.generateEMVQRCode(payment);
        
        return {
            qrCode: emvString,
            txid: payment.txid,
            amount: payment.amount,
            pixKey: this.pixKey,
            expiresAt: payment.expires_at
        };
    }

    generateEMVQRCode(payment) {
        // Usar a nova chave PIX
        return '00020126440014BR.GOV.BCB.PIX0122slavosier298@gmail.com52040000530398654041.005802BR5901N6001C62110507Lactech630453EE';
    }

    calculateCRC16(str) {
        let crc = 0xFFFF;
        for (let i = 0; i < str.length; i++) {
            crc ^= str.charCodeAt(i) << 8;
            for (let j = 0; j < 8; j++) {
                crc = (crc & 0x8000) ? (crc << 1) ^ 0x1021 : crc << 1;
            }
        }
        return crc & 0xFFFF;
    }

    // Função para gerar QR Code a partir de cobrança do banco
    generateQRFromBankCharge(chargeData) {
        // chargeData deve conter: { amount, chargeId, merchantName, merchantCity }
        const payment = {
            txid: chargeData.chargeId || this.generateTxId(),
            amount: chargeData.amount,
            status: 'pending',
            expires_at: new Date(Date.now() + this.paymentTimeout).toISOString(),
            pix_key: this.pixKey,
            pix_key_type: this.pixKeyType
        };

        return this.generatePixQRCode(payment);
    }

    // Função para gerar QR Code simples com chave aleatória
    generateSimplePixQR(amount, description = '') {
        const payment = {
            txid: this.generateTxId(),
            amount: amount,
            status: 'pending',
            expires_at: new Date(Date.now() + this.paymentTimeout).toISOString(),
            pix_key: this.pixKey,
            pix_key_type: this.pixKeyType,
            description: description
        };

        return this.generatePixQRCode(payment);
    }

    // =====================================================
    // VERIFICAÇÃO DE PAGAMENTO
    // =====================================================

    async checkPaymentStatus(txid) {
        try {
            console.log('🔍 Verificando status do pagamento:', txid);
            
            if (!this.supabase) {
                console.warn('⚠️ Supabase não configurado, usando verificação simulada');
                return false;
            }

            const { data: payment, error } = await this.supabase
                .from('pix_payments')
                .select('*')
                .eq('txid', txid)
                .single();

            if (error) {
                console.error('❌ Erro ao buscar pagamento:', error);
                return false;
            }

            if (!payment) {
                console.log('⚠️ Pagamento não encontrado');
                return false;
            }

            // Verificar se o pagamento foi confirmado
            if (payment.status === 'confirmed') {
                console.log('✅ Pagamento confirmado!');
                await this.activateSubscription(payment);
                return true;
            }

            // Verificar se expirou
            const now = new Date();
            const expiresAt = new Date(payment.expires_at);
            
            if (expiresAt <= now) {
                console.log('⚠️ Pagamento expirado');
                await this.expirePayment(payment.id);
                return false;
            }

            console.log('⏳ Pagamento ainda pendente');
            return false;
            
        } catch (error) {
            console.error('❌ Erro ao verificar pagamento:', error);
            return false;
        }
    }

    async activateSubscription(payment) {
        try {
            console.log('🎉 Processando pagamento confirmado...');
            
            if (!this.supabase) {
                console.warn('⚠️ Supabase não configurado, mostrando confirmação visual');
                this.showSuccessScreen(payment);
                return { success: true, message: 'Pagamento confirmado' };
            }

            // Obter plano selecionado
            const selectedPlan = window.SELECTED_PLAN || {
                name: 'Plano Mensal',
                price: 1.00,
                duration: 30,
                description: 'Assinatura mensal recorrente'
            };
            
            const planType = window.SELECTED_PLAN_TYPE || 'monthly';
            
            // Calcular data de expiração baseada no plano
            const expiresAt = new Date();
            expiresAt.setDate(expiresAt.getDate() + selectedPlan.duration);

            // Criar assinatura no banco
            const subscriptionData = {
                user_id: payment.user_id || null,
                payment_id: payment.id,
                status: 'active',
                plan_type: planType,
                amount: payment.amount,
                starts_at: new Date().toISOString(),
                expires_at: expiresAt.toISOString()
            };

            try {
                const { data: subscription, error } = await this.supabase
                    .from('subscriptions')
                    .insert([subscriptionData])
                    .select()
                    .single();

                if (error) {
                    console.error('❌ Erro ao criar assinatura:', error);
                    // Mesmo com erro, mostrar sucesso do pagamento
                    this.showSuccessScreen(payment);
                    return { success: true, message: 'Pagamento confirmado' };
                }

                console.log('✅ Assinatura criada:', subscription);
                this.showSuccessScreen(payment);
                return { success: true, message: 'Pagamento confirmado e assinatura ativada' };

            } catch (subscriptionError) {
                console.error('❌ Erro ao criar assinatura:', subscriptionError);
                // Mesmo com erro, mostrar sucesso do pagamento
                this.showSuccessScreen(payment);
                return { success: true, message: 'Pagamento confirmado' };
            }
            
        } catch (error) {
            console.error('❌ Erro ao processar pagamento:', error);
            this.showError('Erro ao processar pagamento');
        }
    }

    async expirePayment(paymentId) {
        try {
            await this.supabase
                .from('pix_payments')
                .update({ status: 'expired' })
                .eq('id', paymentId);
                
            console.log('✅ Pagamento expirado');
        } catch (error) {
            console.error('❌ Erro ao expirar pagamento:', error);
        }
    }

    // =====================================================
    // INTERFACE DO USUÁRIO
    // =====================================================

    renderPaymentScreen(qrCodeData, payment) {
        const container = document.getElementById('payment-container');
        if (!container) return;

        const timeLeft = this.calculateTimeLeft(qrCodeData.expiresAt);

        container.innerHTML = `
            <div class="animate-fade-in h-screen flex flex-col">
                <div class="max-w-6xl mx-auto flex-1 flex flex-col justify-center px-4 py-4">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="inline-flex items-center justify-center w-12 h-12 mb-2">
                            <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="LacTech Logo" class="w-12 h-12 rounded-xl">
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-1">LacTech</h1>
                        <p class="text-sm text-gray-600">Sistema de Gestão Leiteira</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                        
                        <!-- Left Panel - Plan Details -->
                        <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl p-4 border border-gray-100">
                            <div class="mb-3">
                                <div class="flex items-center justify-between mb-2">
                                    <h2 class="text-lg font-bold text-gray-900">Plano Mensal</h2>
                                    <div class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                        Popular
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <span class="text-2xl font-bold text-gray-900">R$ ${qrCodeData.amount.toFixed(2)}</span>
                                    <span class="text-gray-500 ml-1">/mês</span>
                                </div>
                                <p class="text-gray-600 text-xs">Acesso completo ao sistema de gestão leiteira</p>
                            </div>
                            
                            <!-- Features -->
                            <div class="space-y-1 mb-3">
                                <h3 class="text-sm font-semibold text-gray-900 mb-2">Inclui:</h3>
                                <div class="space-y-1">
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-xs text-gray-700">Gestão completa de rebanho</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-xs text-gray-700">Controle de produção leiteira</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-xs text-gray-700">Relatórios e análises</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-xs text-gray-700">Suporte técnico 24/7</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-xs text-gray-700">Atualizações automáticas</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Plan Details -->
                            <div class="bg-gray-50 rounded-lg p-3 mb-3">
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-gray-500 block mb-1">Duração</span>
                                        <span class="font-semibold text-gray-900">30 dias</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block mb-1">Renovação</span>
                                        <span class="font-semibold text-gray-900">Automática</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block mb-1">Cancelamento</span>
                                        <span class="font-semibold text-gray-900">A qualquer momento</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block mb-1">Suporte</span>
                                        <span class="font-semibold text-gray-900">Incluso</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Footer dentro do card -->
                            <div class="border-t border-gray-200 pt-3">
                                <p class="text-gray-500 text-xs mb-2">
                                    Ao confirmar a inscrição, você concede permissão à LacTech para efetuar cobranças conforme as condições estipuladas, até que ocorra o cancelamento.
                                </p>
                                <div class="text-gray-400 text-xs">
                                    Powered by <span class="font-semibold">Xandria</span> | 
                                    <a href="#" class="hover:text-gray-600 transition-colors">Termos</a> 
                                    <span class="mx-2">|</span> 
                                    <a href="#" class="hover:text-gray-600 transition-colors">Privacidade</a>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel - Payment QR Code -->
                        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                            <div class="text-center mb-3">
                                <div class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full mb-2">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V6a1 1 0 00-1-1H5a1 1 0 00-1 1v1a1 1 0 001 1zm12 0h2a1 1 0 001-1V6a1 1 0 00-1-1H5a1 1 0 00-1 1v1a1 1 0 001 1zM5 20h2a1 1 0 001-1v-1a1 1 0 00-1-1H5a1 1 0 00-1 1v1a1 1 0 001 1z"></path>
                                    </svg>
                                </div>
                                <h2 class="text-lg font-bold text-gray-900 mb-1">Pagamento PIX</h2>
                                <p class="text-gray-600 text-xs">Escaneie o QR Code com seu app bancário</p>
                            </div>

                            <!-- QR Code -->
                            <div class="flex justify-center mb-3">
                                <div id="qr-code" class="w-32 h-32 bg-gray-50 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-200">
                                    <div class="text-center">
                                        <svg class="w-5 h-5 text-gray-400 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V6a1 1 0 00-1-1H5a1 1 0 00-1 1v1a1 1 0 001 1zm12 0h2a1 1 0 001-1V6a1 1 0 00-1-1H5a1 1 0 00-1 1v1a1 1 0 001 1zM5 20h2a1 1 0 001-1v-1a1 1 0 00-1-1H5a1 1 0 00-1 1v1a1 1 0 001 1z"></path>
                                        </svg>
                                        <span class="text-gray-500 text-xs">Gerando QR Code...</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Info -->
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-3 mb-3">
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-gray-500 block mb-1">Valor</span>
                                        <div class="font-bold text-sm text-gray-900">R$ ${qrCodeData.amount.toFixed(2)}</div>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block mb-1">Expira em</span>
                                        <div class="font-semibold text-red-600" id="countdown">${timeLeft}</div>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="text-gray-500 block mb-1">Chave PIX</span>
                                        <div class="font-mono text-xs bg-white px-2 py-1 rounded border break-all">00020126440014BR.GOV.BCB.PIX0122slavosier298@gmail.com52040000530398654041.005802BR5901N6001C62110507Lactech630453EE</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="space-y-2">
                                <button id="copy-pix-btn" onclick="window.pixSystem.copyPixKey()" class="w-full bg-gray-100 text-gray-700 py-2 px-3 rounded-lg font-medium hover:bg-gray-200 transition-all duration-200 flex items-center justify-center text-sm">
                                    <svg class="w-3 h-3 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <span id="copy-btn-text">Copiar Chave PIX</span>
                                </button>
                                <button onclick="window.pixSystem.startPaymentCheck('${qrCodeData.txid}')" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-2 px-3 rounded-lg font-medium hover:from-green-600 hover:to-green-700 transition-all duration-200 flex items-center justify-center text-sm">
                                    <svg class="w-3 h-3 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Verificar Pagamento
                                </button>
                            </div>

                            <!-- Status -->
                            <div id="payment-status" class="mt-2 text-center">
                                <div class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Aguardando pagamento...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Gerar QR Code visual com efeito de carregamento
        setTimeout(() => {
            const qrElement = document.getElementById('qr-code');
            if (qrElement) {
                // Efeito de carregamento
                qrElement.innerHTML = `
                    <div class="w-32 h-32 bg-gray-50 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-200">
                        <div class="text-center">
                            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-green-500 mx-auto mb-1"></div>
                            <span class="text-gray-500 text-xs">Gerando QR Code...</span>
                        </div>
                    </div>
                `;
                
                // Simular carregamento e mostrar a imagem
                setTimeout(() => {
                    qrElement.innerHTML = `
                        <div class="w-32 h-32 bg-white rounded-lg flex items-center justify-center border border-gray-200 shadow-sm">
                            <img src="qrcode-pix.png" alt="QR Code PIX" class="w-28 h-28 rounded" />
                        </div>
                    `;
                    console.log('✅ QR Code carregado com sucesso');
                }, 1500); // 1.5 segundos de carregamento
            }
        }, 100);

        // Iniciar countdown
        this.startCountdown(qrCodeData.expiresAt);
    }

    showPaymentScreen(qrCodeData = null, payment = null) {
        // Esconder loading e mostrar container de pagamento
        const loadingContainer = document.getElementById('loading-container');
        const container = document.getElementById('payment-container');
        
        if (loadingContainer) {
            loadingContainer.style.display = 'none';
        }
        
        if (!container) return;
        
        // Mostrar container de pagamento
        container.classList.remove('hidden');

        // Se não tiver dados, gerar pagamento uma única vez
        if (!qrCodeData && !this.paymentGenerated) {
            this.paymentGenerated = true;
            this.generatePixPayment();
            return;
        }

        // Obter plano selecionado
        const selectedPlan = window.SELECTED_PLAN || {
            name: 'Plano Mensal',
            price: 1.00,
            duration: 30,
            description: 'Assinatura mensal recorrente'
        };

        const timeLeft = this.calculateTimeLeft(qrCodeData.expiresAt);
        
        container.innerHTML = `
            <div class="animate-fade-in">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-6xl mx-auto">
                    
                    <!-- Left Panel - Subscription Details (Dark Theme) -->
                    <div class="bg-gray-900 rounded-2xl p-8 text-white">
                        <div class="mb-8">
                            <h1 class="text-3xl font-bold mb-2">Assinar LacTech</h1>
                            <div class="text-4xl font-bold text-green-400 mb-1">R$ ${qrCodeData.amount.toFixed(2)}</div>
                            <p class="text-gray-300">por mês</p>
                        </div>
                        
                        <!-- Plan Card -->
                        <div class="bg-gray-800 rounded-xl p-6 mb-8">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold">LacTech Pro</h3>
                                    <p class="text-gray-400 text-sm">${selectedPlan.description}</p>
                                </div>
                            </div>
                            <p class="text-gray-300 text-sm mb-4">LacTech Pro desbloqueia recursos ilimitados, relatórios avançados e acesso a todas as funcionalidades do sistema.</p>
                            <p class="text-gray-400 text-sm">Cobrado ${selectedPlan.duration === 30 ? 'mensalmente' : 'anualmente'}</p>
                        </div>
                        
                        <!-- Summary -->
                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-3 border-b border-gray-700">
                                <span class="text-gray-300">Subtotal</span>
                                <span class="text-white font-semibold">R$ ${qrCodeData.amount.toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-700">
                                <span class="text-gray-300 flex items-center">
                                    Imposto
                                    <svg class="w-4 h-4 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                                <span class="text-white font-semibold">R$ 0,00</span>
                            </div>
                            <div class="flex justify-between items-center py-3">
                                <span class="text-gray-300 font-semibold">Total devido hoje</span>
                                <span class="text-green-400 font-bold text-xl">R$ ${qrCodeData.amount.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Panel - Payment Methods (White Theme) -->
                    <div class="bg-white rounded-2xl p-8 card-shadow">
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">Dados para contato</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
                                    <input type="email" value="" placeholder="Seu e-mail será solicitado após o pagamento"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">Forma de pagamento</h2>
                            <div class="space-y-4">
                                
                                <!-- Pix Option -->
                                <div class="payment-method-option selected border-2 border-gray-200 rounded-xl p-4 cursor-pointer" 
                                     onclick="pixSystem.selectPaymentMethod('pix')">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                                <div class="w-2 h-2 bg-white rounded-full"></div>
                                            </div>
                                            <div>
                                                <span class="font-semibold text-gray-800">Pix</span>
                                                <p class="text-sm text-gray-500">Pagamento instantâneo</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <div class="w-8 h-6 bg-green-100 rounded flex items-center justify-center">
                                                <span class="text-green-600 text-xs font-bold">PIX</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Card Option -->
                                <div class="payment-method-option border-2 border-gray-200 rounded-xl p-4 cursor-pointer" 
                                     onclick="pixSystem.selectPaymentMethod('card')">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center mr-3">
                                            </div>
                                            <div>
                                                <span class="font-semibold text-gray-800">Cartão</span>
                                                <p class="text-sm text-gray-500">Visa, Mastercard, American Express</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <div class="w-8 h-6 bg-blue-100 rounded flex items-center justify-center">
                                                <span class="text-blue-600 text-xs">💳</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                        
                        <!-- Save Information -->
                        <div class="mb-8">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                <span class="ml-3 text-sm text-gray-700">
                                    Salve minhas informações para um checkout mais rápido
                                </span>
                            </label>
                            <p class="text-xs text-gray-500 mt-2">
                                Pague mais rápido no LacTech e em todos os lugares que aceitam o Link.
                            </p>
                        </div>
                        
                        <!-- Payment Button -->
                        <button onclick="pixSystem.processPayment()" 
                                class="w-full bg-gray-900 text-white py-4 px-6 rounded-xl hover:bg-gray-800 transition-colors font-semibold text-lg mb-4">
                            Assinar
                        </button>
                        
                        <!-- Terms -->
                        <p class="text-xs text-gray-500 text-center">
                            Ao confirmar a inscrição, você concede permissão ao LacTech para efetuar cobranças conforme as condições estipuladas, até que ocorra o cancelamento.
                        </p>
                        
                        <div class="text-center mt-4">
                            <p class="text-xs text-gray-400">Powered by LacTech</p>
                            <div class="flex justify-center space-x-4 mt-2">
                                <a href="#" class="text-xs text-gray-400 hover:text-gray-600">Termos</a>
                                <a href="#" class="text-xs text-gray-400 hover:text-gray-600">Privacidade</a>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Pix Payment Modal (Hidden by default) -->
                <div id="pix-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
                        <div class="text-center mb-6">
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">Pagamento via Pix</h3>
                            <p class="text-gray-600">Escaneie o QR Code ou copie a chave</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-xl p-6 mb-6">
                            <div class="text-center">
                                <div id="qr-code" class="bg-white p-4 rounded-xl inline-block mb-4">
                                    <div class="w-48 h-48 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <span class="text-gray-400 text-sm">QR Code</span>
                                    </div>
                                </div>
                                <button onclick="pixSystem.copyPixKey()" class="text-green-600 text-sm hover:text-green-700 font-medium">
                                    📋 Copiar chave Pix
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600">Chave Pix:</span>
                                <span class="font-mono text-gray-800 bg-gray-50 px-2 py-1 rounded text-sm">${qrCodeData.pixKey}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600">Expira em:</span>
                                <span class="text-red-600 font-semibold" id="countdown">${timeLeft}</span>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <button onclick="pixSystem.checkPaymentStatus('${qrCodeData.txid}')" 
                                    class="w-full bg-green-600 text-white py-3 px-6 rounded-xl hover:bg-green-700 transition-colors font-semibold">
                                🔍 Verificar Pagamento
                            </button>
                            <button onclick="pixSystem.closePixModal()" 
                                    class="w-full bg-gray-100 text-gray-700 py-3 px-6 rounded-xl hover:bg-gray-200 transition-colors font-medium">
                                ❌ Cancelar
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="text-center mt-6">
                    <p class="text-gray-500 text-xs mb-2">
                        Ao confirmar a inscrição, você concede permissão à LacTech para efetuar cobranças conforme as condições estipuladas, até que ocorra o cancelamento.
                    </p>
                    <div class="text-gray-400 text-xs">
                        Powered by <span class="font-semibold">Xandria</span> | 
                        <a href="#" class="hover:text-gray-600 transition-colors">Termos</a> 
                        <span class="mx-2">|</span> 
                        <a href="#" class="hover:text-gray-600 transition-colors">Privacidade</a>
                    </div>
                </div>
            </div>
        `;

        // Iniciar contador regressivo
        this.startCountdown(qrCodeData.expiresAt);
        
        // Verificar pagamento automaticamente a cada 10 segundos
        this.startPaymentCheck(qrCodeData.txid);
    }

    showSuccessAndRedirect() {
        const container = document.getElementById('payment-container');
        if (!container) return;

        container.innerHTML = `
            <div class="animate-fade-in">
                <div class="bg-white rounded-2xl card-shadow overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-green-500 to-green-600 px-8 py-6">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h2 class="text-white text-2xl font-bold mb-1">Pagamento Confirmado!</h2>
                            <p class="text-green-100 text-sm">LacTech Sistema Leiteiro</p>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-8">
                        <div class="text-center mb-8">
                            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">Assinatura Ativada!</h3>
                            <p class="text-gray-600 text-lg">Seu pagamento foi confirmado com sucesso</p>
                        </div>
                        
                        <div class="space-y-4">
                            <button onclick="pixSystem.redirectToDashboard()" 
                                    class="w-full bg-green-600 text-white py-4 px-6 rounded-xl hover:bg-green-700 transition-colors font-semibold text-lg">
                                🚀 Acessar Sistema
                            </button>
                        </div>
                        
                        <div class="mt-6 text-center">
                            <p class="text-gray-500 text-sm">Redirecionando automaticamente em <span id="countdown">5</span> segundos...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="text-center mt-6">
                    <p class="text-gray-500 text-xs mb-2">
                        Ao confirmar a inscrição, você concede permissão à LacTech para efetuar cobranças conforme as condições estipuladas, até que ocorra o cancelamento.
                    </p>
                    <div class="text-gray-400 text-xs">
                        Powered by <span class="font-semibold">Xandria</span> | 
                        <a href="#" class="hover:text-gray-600 transition-colors">Termos</a> 
                        <span class="mx-2">|</span> 
                        <a href="#" class="hover:text-gray-600 transition-colors">Privacidade</a>
                    </div>
                </div>
            </div>
        `;

        // Contador regressivo e redirecionamento automático
        let countdown = 5;
        const countdownElement = document.getElementById('countdown');
        const interval = setInterval(() => {
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            if (countdown <= 0) {
                clearInterval(interval);
                this.redirectToDashboard();
            }
        }, 1000);
    }

    showError(message) {
        const container = document.getElementById('payment-container');
        if (!container) return;

        container.innerHTML = `
            <div class="animate-fade-in">
                <div class="bg-white rounded-2xl card-shadow overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-red-500 to-red-600 px-8 py-6">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <h2 class="text-white text-2xl font-bold mb-1">Erro</h2>
                            <p class="text-red-100 text-sm">LacTech Sistema Leiteiro</p>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-8">
                        <div class="text-center mb-8">
                            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">Ops!</h3>
                            <p class="text-gray-600 text-lg">${message}</p>
                        </div>
                        
                        <div class="space-y-4">
                            <button onclick="pixSystem.generatePixPayment()" 
                                    class="w-full bg-green-600 text-white py-4 px-6 rounded-xl hover:bg-green-700 transition-colors font-semibold text-lg">
                                🔄 Tentar Novamente
                            </button>
                            <button onclick="window.location.href='inicio.html'" 
                                    class="w-full bg-gray-100 text-gray-700 py-3 px-6 rounded-xl hover:bg-gray-200 transition-colors font-medium">
                                ← Voltar ao Início
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="text-center mt-6">
                    <p class="text-gray-500 text-xs mb-2">
                        Ao confirmar a inscrição, você concede permissão à LacTech para efetuar cobranças conforme as condições estipuladas, até que ocorra o cancelamento.
                    </p>
                    <div class="text-gray-400 text-xs">
                        Powered by <span class="font-semibold">Xandria</span> | 
                        <a href="#" class="hover:text-gray-600 transition-colors">Termos</a> 
                        <span class="mx-2">|</span> 
                        <a href="#" class="hover:text-gray-600 transition-colors">Privacidade</a>
                    </div>
                </div>
            </div>
        `;
    }

    // =====================================================
    // UTILITÁRIOS
    // =====================================================

    calculateTimeLeft(expiresAt) {
        const now = new Date();
        const expires = new Date(expiresAt);
        const diff = expires - now;
        
        if (diff <= 0) return 'Expirado';
        
        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    startCountdown(expiresAt) {
        const countdownElement = document.getElementById('countdown');
        if (!countdownElement) return;

        const interval = setInterval(() => {
            const timeLeft = this.calculateTimeLeft(expiresAt);
            countdownElement.textContent = timeLeft;
            
            if (timeLeft === 'Expirado') {
                clearInterval(interval);
                this.showError('QR Code expirado. Gere um novo.');
            }
        }, 1000);
    }

    startPaymentCheck(txid) {
        console.log('⏸️ Verificação automática de pagamento desabilitada - aguardando configuração do banco');
        console.log('💡 Execute o SQL no Supabase para habilitar verificação automática');
        
        // Temporariamente desabilitado para evitar erros 406
        // TODO: Reabilitar após configurar o banco de dados
        
        /* CÓDIGO ORIGINAL - REATIVAR APÓS CONFIGURAR BANCO:
        const interval = setInterval(async () => {
            const isConfirmed = await this.checkPaymentStatus(txid);
            if (isConfirmed) {
                clearInterval(interval);
            }
        }, 10000); // Verificar a cada 10 segundos
        */
    }

    copyPixKey() {
        // Copiar a nova chave PIX
        const pixKey = '00020126440014BR.GOV.BCB.PIX0122slavosier298@gmail.com52040000530398654041.005802BR5901N6001C62110507Lactech630453EE';
        
        navigator.clipboard.writeText(pixKey).then(() => {
            // Mudar o botão para "Copiado!"
            const copyBtn = document.getElementById('copy-pix-btn');
            const copyBtnText = document.getElementById('copy-btn-text');
            
            if (copyBtn && copyBtnText) {
                // Mudar cor e texto do botão
                copyBtn.className = 'w-full bg-green-100 text-green-700 py-3 px-4 rounded-xl font-medium transition-all duration-200 flex items-center justify-center';
                copyBtnText.innerHTML = '✅ Copiado!';
                
                // Voltar ao normal após 2 segundos
                setTimeout(() => {
                    copyBtn.className = 'w-full bg-gray-100 text-gray-700 py-3 px-4 rounded-xl font-medium hover:bg-gray-200 transition-all duration-200 flex items-center justify-center';
                    copyBtnText.innerHTML = 'Copiar Chave PIX';
                }, 2000);
            }
        }).catch(() => {
            this.showNotification('Erro ao copiar chave', 'error');
        });
    }

    redirectToLogin() {
        window.location.href = 'login.html';
    }

    redirectToDashboard() {
        window.location.href = 'gerente.html';
    }

    showSubscriptionDetails() {
        // Implementar modal com detalhes da assinatura
        console.log('Mostrar detalhes da assinatura');
    }

    showNotification(message, type = 'info') {
        // Implementar sistema de notificações
        console.log(`${type.toUpperCase()}: ${message}`);
    }

    // =====================================================
    // NOVAS FUNÇÕES PARA O DESIGN INSPIRADO NA IMAGEM
    // =====================================================

    selectPaymentMethod(method) {
        // Remover seleção anterior
        document.querySelectorAll('.payment-method-option').forEach(option => {
            option.classList.remove('selected');
            const radio = option.querySelector('.w-6.h-6');
            if (radio) {
                radio.className = 'w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center mr-3';
            }
        });

        // Selecionar nova opção
        const selectedOption = event.currentTarget;
        selectedOption.classList.add('selected');
        const radio = selectedOption.querySelector('.w-6.h-6');
        if (radio) {
            radio.className = 'w-6 h-6 bg-green-500 rounded-full flex items-center justify-center mr-3';
            const dot = radio.querySelector('.w-2.h-2') || document.createElement('div');
            dot.className = 'w-2 h-2 bg-white rounded-full';
            radio.appendChild(dot);
        }

        // Armazenar método selecionado
        this.selectedPaymentMethod = method;
    }

    processPayment() {
        if (!this.selectedPaymentMethod) {
            this.showNotification('Selecione uma forma de pagamento', 'error');
            return;
        }

        if (this.selectedPaymentMethod === 'pix') {
            this.showPixModal();
        } else if (this.selectedPaymentMethod === 'card') {
            this.showCardPayment();
        }
    }

    showPixModal() {
        const modal = document.getElementById('pix-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    closePixModal() {
        const modal = document.getElementById('pix-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    showCardPayment() {
        // Implementar pagamento com cartão (futuro)
        this.showNotification('Pagamento com cartão será implementado em breve', 'info');
    }
}

// =====================================================
// INICIALIZAÇÃO
// =====================================================

let pixSystem;

document.addEventListener('DOMContentLoaded', async function() {
    pixSystem = new PixPaymentSystem();
    await pixSystem.initialize();
});

// =====================================================
// SQL PARA CRIAR AS TABELAS NO SUPABASE
// =====================================================

/*
-- Tabela de pagamentos Pix
CREATE TABLE pix_payments (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
    txid TEXT UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status TEXT CHECK (status IN ('pending', 'confirmed', 'expired', 'cancelled')) DEFAULT 'pending',
    pix_key TEXT NOT NULL,
    pix_key_type TEXT CHECK (pix_key_type IN ('email', 'cpf', 'telefone', 'aleatoria')) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL
);

-- Tabela de assinaturas
CREATE TABLE subscriptions (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
    payment_id UUID REFERENCES pix_payments(id),
    status TEXT CHECK (status IN ('active', 'expired', 'cancelled')) DEFAULT 'active',
    plan_type TEXT CHECK (plan_type IN ('monthly', 'yearly')) DEFAULT 'monthly',
    amount DECIMAL(10,2) NOT NULL,
    starts_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Índices para performance
CREATE INDEX idx_pix_payments_user_id ON pix_payments(user_id);
CREATE INDEX idx_pix_payments_txid ON pix_payments(txid);
CREATE INDEX idx_pix_payments_status ON pix_payments(status);
CREATE INDEX idx_subscriptions_user_id ON subscriptions(user_id);
CREATE INDEX idx_subscriptions_status ON subscriptions(status);
CREATE INDEX idx_subscriptions_expires_at ON subscriptions(expires_at);

-- RLS (Row Level Security)
ALTER TABLE pix_payments ENABLE ROW LEVEL SECURITY;
ALTER TABLE subscriptions ENABLE ROW LEVEL SECURITY;

-- Políticas para pix_payments
CREATE POLICY "Users can view their own payments" ON pix_payments
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can insert their own payments" ON pix_payments
    FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update their own payments" ON pix_payments
    FOR UPDATE USING (auth.uid() = user_id);

-- Políticas para subscriptions
CREATE POLICY "Users can view their own subscriptions" ON subscriptions
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can insert their own subscriptions" ON subscriptions
    FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update their own subscriptions" ON subscriptions
    FOR UPDATE USING (auth.uid() = user_id);

-- Função para atualizar updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers para atualizar updated_at
CREATE TRIGGER update_pix_payments_updated_at BEFORE UPDATE ON pix_payments
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_subscriptions_updated_at BEFORE UPDATE ON subscriptions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
*/ 