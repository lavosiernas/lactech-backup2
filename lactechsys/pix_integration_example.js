// =====================================================
// EXEMPLO DE INTEGRAÇÃO - GERADOR QR CODES PIX
// =====================================================

// Este arquivo demonstra como integrar o gerador de QR codes PIX
// com o sistema de pagamento existente

// =====================================================
// CONFIGURAÇÃO INICIAL
// =====================================================

// Importar o gerador de QR codes
// <script src="pix_qr_generator.js"></script>

// Inicializar gerador
const qrGenerator = new PixQRGenerator();

// =====================================================
// EXEMPLO 1: INTEGRAÇÃO COM COBRANÇA DO BANCO
// =====================================================

class BankChargeIntegration {
    constructor() {
        this.qrGenerator = new PixQRGenerator();
    }

    // Simular criação de cobrança no banco
    async createBankCharge(amount, description) {
        // Aqui você faria a chamada real para a API do banco
        console.log('🏦 Criando cobrança no banco...');
        
        // Simular resposta do banco
        const bankResponse = {
            chargeId: `COBRANCA_${Date.now()}`,
            amount: amount,
            status: 'pending',
            expiresAt: new Date(Date.now() + 30 * 60 * 1000).toISOString(),
            bankReference: `REF_${Math.random().toString(36).substring(2, 10)}`
        };

        console.log('✅ Cobrança criada:', bankResponse);
        return bankResponse;
    }

    // Gerar QR code baseado na cobrança do banco
    async generateQRFromBankCharge(amount, description) {
        try {
            // 1. Criar cobrança no banco
            const bankCharge = await this.createBankCharge(amount, description);
            
            // 2. Preparar dados para QR code
            const chargeData = {
                amount: bankCharge.amount,
                chargeId: bankCharge.chargeId,
                merchantName: 'LacTech Sistema Leiteiro',
                merchantCity: 'SAO PAULO',
                bankReference: bankCharge.bankReference
            };
            
            // 3. Gerar QR code
            const qrData = this.qrGenerator.generateQRFromBankCharge(chargeData);
            
            // 4. Salvar no banco de dados (opcional)
            await this.saveQRCodeToDatabase(qrData, bankCharge);
            
            console.log('🎯 QR Code gerado da cobrança:', qrData);
            return qrData;
            
        } catch (error) {
            console.error('❌ Erro ao gerar QR code da cobrança:', error);
            throw error;
        }
    }

    // Salvar QR code no banco de dados
    async saveQRCodeToDatabase(qrData, bankCharge) {
        // Aqui você salvaria no Supabase ou outro banco
        const qrRecord = {
            id: qrData.txid,
            qr_code: qrData.qrCode,
            amount: qrData.amount,
            pix_key: qrData.pixKey,
            bank_charge_id: bankCharge.chargeId,
            bank_reference: bankCharge.bankReference,
            status: 'pending',
            created_at: new Date().toISOString(),
            expires_at: qrData.expiresAt
        };

        console.log('💾 Salvando QR code no banco:', qrRecord);
        // await supabase.from('pix_qr_codes').insert(qrRecord);
    }
}

// =====================================================
// EXEMPLO 2: INTEGRAÇÃO COM SISTEMA DE PAGAMENTO
// =====================================================

class PaymentSystemIntegration {
    constructor() {
        this.qrGenerator = new PixQRGenerator();
        this.paymentSystem = new PixPaymentSystem();
    }

    // Processar pagamento com QR code
    async processPaymentWithQR(amount, planType = 'monthly') {
        try {
            console.log('💳 Processando pagamento com QR code...');
            
            // 1. Gerar QR code
            const qrData = this.qrGenerator.generateSimplePixQR(amount, `Assinatura ${planType}`);
            
            // 2. Mostrar interface de pagamento
            this.showPaymentInterface(qrData);
            
            // 3. Iniciar verificação de pagamento
            this.startPaymentVerification(qrData.txid);
            
            return qrData;
            
        } catch (error) {
            console.error('❌ Erro no processamento:', error);
            throw error;
        }
    }

    // Mostrar interface de pagamento
    showPaymentInterface(qrData) {
        const container = document.getElementById('payment-container');
        if (!container) return;

        container.innerHTML = `
            <div class="bg-white rounded-2xl p-8 card-shadow">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Pagamento via PIX</h2>
                    <p class="text-gray-600">Escaneie o QR Code para pagar</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- QR Code -->
                    <div class="text-center">
                        <div id="qr-code-display" class="bg-gray-50 rounded-xl p-6 mb-4">
                            <div class="w-48 h-48 bg-gray-100 rounded-lg flex items-center justify-center mx-auto">
                                <span class="text-gray-400 text-sm">QR Code</span>
                            </div>
                        </div>
                        <button onclick="paymentIntegration.copyPixKey()" 
                                class="text-green-600 text-sm hover:text-green-700 font-medium">
                            📋 Copiar chave PIX
                        </button>
                    </div>
                    
                    <!-- Informações -->
                    <div class="space-y-4">
                        <div class="bg-gray-50 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-800 mb-2">Detalhes do Pagamento</h3>
                            <div class="space-y-2 text-sm">
                                <div><strong>Valor:</strong> R$ ${qrData.amount.toFixed(2)}</div>
                                <div><strong>Chave PIX:</strong> ${qrData.pixKey}</div>
                                <div><strong>ID:</strong> ${qrData.txid}</div>
                                <div><strong>Expira:</strong> ${new Date(qrData.expiresAt).toLocaleString('pt-BR')}</div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-800 mb-2">Código EMV</h3>
                            <div class="bg-gray-100 p-3 rounded-lg">
                                <code class="text-xs break-all">${qrData.qrCode}</code>
                            </div>
                        </div>
                        
                        <button onclick="paymentIntegration.checkPaymentStatus('${qrData.txid}')" 
                                class="w-full bg-green-600 text-white py-3 px-6 rounded-xl hover:bg-green-700 transition-colors font-semibold">
                            🔍 Verificar Pagamento
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Gerar QR code visual (requer biblioteca QRCode)
        this.generateVisualQRCode(qrData.qrCode);
    }

    // Gerar QR code visual
    generateVisualQRCode(qrCode) {
        const qrDisplay = document.getElementById('qr-code-display');
        if (!qrDisplay) return;

        qrDisplay.innerHTML = '';
        
        // Se a biblioteca QRCode estiver disponível
        if (typeof QRCode !== 'undefined') {
            QRCode.toCanvas(qrDisplay, qrCode, {
                width: 192,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            }, function (error) {
                if (error) {
                    qrDisplay.innerHTML = '<div class="w-48 h-48 bg-gray-100 rounded-lg flex items-center justify-center"><span class="text-red-400 text-sm">Erro ao gerar QR Code</span></div>';
                }
            });
        } else {
            qrDisplay.innerHTML = '<div class="w-48 h-48 bg-gray-100 rounded-lg flex items-center justify-center"><span class="text-gray-400 text-sm">QR Code: ' + qrCode.substring(0, 20) + '...</span></div>';
        }
    }

    // Verificar status do pagamento
    async checkPaymentStatus(txid) {
        try {
            console.log('🔍 Verificando pagamento:', txid);
            
            // Aqui você faria a verificação real com o banco
            const isPaid = await this.verifyWithBank(txid);
            
            if (isPaid) {
                this.showPaymentSuccess();
            } else {
                this.showPaymentPending();
            }
            
        } catch (error) {
            console.error('❌ Erro ao verificar pagamento:', error);
            this.showPaymentError();
        }
    }

    // Simular verificação com banco
    async verifyWithBank(txid) {
        // Simular verificação (em produção, seria uma chamada real para o banco)
        return Math.random() > 0.7; // 30% de chance de estar pago
    }

    // Iniciar verificação automática
    startPaymentVerification(txid) {
        console.log('⏰ Iniciando verificação automática...');
        
        const interval = setInterval(async () => {
            const isPaid = await this.verifyWithBank(txid);
            if (isPaid) {
                clearInterval(interval);
                this.showPaymentSuccess();
            }
        }, 10000); // Verificar a cada 10 segundos
    }

    // Mostrar sucesso
    showPaymentSuccess() {
        const container = document.getElementById('payment-container');
        if (!container) return;

        container.innerHTML = `
            <div class="bg-white rounded-2xl p-8 card-shadow">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Pagamento Confirmado!</h2>
                    <p class="text-gray-600 mb-6">Seu pagamento foi processado com sucesso.</p>
                    <button onclick="window.location.href='gerente.html'" 
                            class="bg-green-600 text-white py-3 px-6 rounded-xl hover:bg-green-700 transition-colors font-semibold">
                        🚀 Acessar Sistema
                    </button>
                </div>
            </div>
        `;
    }

    // Mostrar pendente
    showPaymentPending() {
        alert('Pagamento ainda pendente. Tente novamente em alguns segundos.');
    }

    // Mostrar erro
    showPaymentError() {
        alert('Erro ao verificar pagamento. Tente novamente.');
    }

    // Copiar chave PIX
    copyPixKey() {
        const pixKey = 'slavosier298@gmail.com'; // Chave padrão
        navigator.clipboard.writeText(pixKey).then(() => {
            alert('Chave PIX copiada!');
        }).catch(() => {
            alert('Erro ao copiar chave');
        });
    }
}

// =====================================================
// EXEMPLO 3: USO SIMPLES
// =====================================================

// Função para gerar QR code simples
function generateSimplePixQR(amount, description) {
    const qrData = qrGenerator.generateSimplePixQR(amount, description);
    console.log('QR Code gerado:', qrData);
    return qrData;
}

// Função para gerar QR code da cobrança
function generateBankChargeQR(amount, chargeId, merchantName, merchantCity) {
    const chargeData = {
        amount: amount,
        chargeId: chargeId,
        merchantName: merchantName,
        merchantCity: merchantCity
    };
    
    const qrData = qrGenerator.generateQRFromBankCharge(chargeData);
    console.log('QR Code da cobrança:', qrData);
    return qrData;
}

// Função para validar QR code
function validatePixQR(qrCode) {
    const validation = qrGenerator.validateQRCode(qrCode);
    console.log('Validação:', validation);
    return validation.valid;
}

// =====================================================
// INICIALIZAÇÃO
// =====================================================

// Criar instâncias para uso global
const bankIntegration = new BankChargeIntegration();
const paymentIntegration = new PaymentSystemIntegration();

// Expor funções globalmente
if (typeof window !== 'undefined') {
    window.bankIntegration = bankIntegration;
    window.paymentIntegration = paymentIntegration;
    window.generateSimplePixQR = generateSimplePixQR;
    window.generateBankChargeQR = generateBankChargeQR;
    window.validatePixQR = validatePixQR;
}

// =====================================================
// EXEMPLOS DE USO
// =====================================================

// Exemplo 1: QR code simples
// generateSimplePixQR(50.00, 'Pagamento LacTech');

// Exemplo 2: QR code da cobrança
// generateBankChargeQR(100.00, 'COBRANCA_12345', 'LacTech', 'SAO PAULO');

// Exemplo 3: Processar pagamento completo
// paymentIntegration.processPaymentWithQR(75.50, 'monthly');

// Exemplo 4: Integração com cobrança do banco
// bankIntegration.generateQRFromBankCharge(150.00, 'Assinatura Anual');

console.log('🚀 Sistema de integração QR Code PIX carregado!');
console.log('💡 Use as funções: generateSimplePixQR, generateBankChargeQR, paymentIntegration.processPaymentWithQR');
