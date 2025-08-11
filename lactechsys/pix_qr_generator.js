// =====================================================
// GERADOR DE QR CODES PIX - EXEMPLOS DE USO
// =====================================================

class PixQRGenerator {
    constructor() {
        this.pixKey = 'slavosier298@gmail.com';
        this.pixKeyType = 'email';
        this.paymentTimeout = 30 * 60 * 1000; // 30 minutos
    }

    // =====================================================
    // GERAÇÃO DE QR CODES PIX
    // =====================================================

    generateTxId() {
        const timestamp = Date.now();
        const random = Math.random().toString(36).substring(2, 15);
        const uniqueId = Math.random().toString(36).substring(2, 10);
        return `PIX_${uniqueId}_${timestamp}_${random}`;
    }

    generateEMVQRCode(payment) {
        // Formato EMV QR Code PIX baseado no exemplo fornecido
        // 00020126580014BR.GOV.BCB.PIX0136ebabf96f-5162-4bd1-95c5-64ffa8e9bfed52040000530398654041.005802BR5924Francisco Lavosier Silva6009SAO PAULO62140510d7tboHbbcE6304802C
        
        let emv = '';
        
        // Payload Format Indicator (00) - 02 = QR Code
        emv += '000201';
        
        // Point of Initiation Method (01) - 12 = Static QR Code
        emv += '010212';
        
        // Merchant Account Information (26) - PIX
        emv += '26';
        
        // GUI (00) - br.gov.bcb.pix
        emv += '0014br.gov.bcb.pix';
        
        // PIX Key (01) - Chave PIX
        const pixKeyLength = this.pixKey.length.toString().padStart(2, '0');
        emv += '01' + pixKeyLength + this.pixKey;
        
        // Merchant Category Code (52) - 0000 = General
        emv += '52040000';
        
        // Transaction Currency (53) - 986 = BRL
        emv += '5303986';
        
        // Transaction Amount (54) - Valor da transação
        const amount = payment.amount.toFixed(2);
        const amountLength = amount.length.toString().padStart(2, '0');
        emv += '54' + amountLength + amount;
        
        // Country Code (58) - BR
        emv += '5802BR';
        
        // Merchant Name (59) - Nome do estabelecimento
        const merchantName = payment.merchantName || 'LacTech Sistema Leiteiro';
        const merchantNameLength = merchantName.length.toString().padStart(2, '0');
        emv += '59' + merchantNameLength + merchantName;
        
        // Merchant City (60) - Cidade do estabelecimento
        const merchantCity = payment.merchantCity || 'BRASIL';
        const merchantCityLength = merchantCity.length.toString().padStart(2, '0');
        emv += '60' + merchantCityLength + merchantCity;
        
        // Additional Data Field Template (62)
        emv += '62';
        
        // Reference Label (05) - ID da transação
        const txidLength = payment.txid.length.toString().padStart(2, '0');
        emv += '05' + txidLength + payment.txid;
        
        // CRC16 (63) - Checksum
        emv += '6304';
        
        const crc = this.calculateCRC16(emv);
        emv += crc.toString(16).toUpperCase().padStart(4, '0');
        
        return emv;
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

    // =====================================================
    // EXEMPLOS DE USO
    // =====================================================

    // Exemplo 1: QR Code simples para pagamento
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

        const qrCode = this.generateEMVQRCode(payment);
        
        return {
            qrCode: qrCode,
            txid: payment.txid,
            amount: payment.amount,
            pixKey: this.pixKey,
            expiresAt: payment.expires_at
        };
    }

    // Exemplo 2: QR Code baseado em cobrança do banco
    generateQRFromBankCharge(chargeData) {
        // chargeData deve conter: { amount, chargeId, merchantName, merchantCity }
        const payment = {
            txid: chargeData.chargeId || this.generateTxId(),
            amount: chargeData.amount,
            status: 'pending',
            expires_at: new Date(Date.now() + this.paymentTimeout).toISOString(),
            pix_key: this.pixKey,
            pix_key_type: this.pixKeyType,
            merchantName: chargeData.merchantName || 'LacTech Sistema Leiteiro',
            merchantCity: chargeData.merchantCity || 'BRASIL'
        };

        const qrCode = this.generateEMVQRCode(payment);
        
        return {
            qrCode: qrCode,
            txid: payment.txid,
            amount: payment.amount,
            pixKey: this.pixKey,
            expiresAt: payment.expires_at,
            merchantName: payment.merchantName,
            merchantCity: payment.merchantCity
        };
    }

    // Exemplo 3: QR Code com chave PIX personalizada
    generateCustomPixQR(amount, customPixKey, customPixKeyType = 'email') {
        const payment = {
            txid: this.generateTxId(),
            amount: amount,
            status: 'pending',
            expires_at: new Date(Date.now() + this.paymentTimeout).toISOString(),
            pix_key: customPixKey,
            pix_key_type: customPixKeyType
        };

        // Temporariamente usar a chave customizada
        const originalKey = this.pixKey;
        this.pixKey = customPixKey;
        this.pixKeyType = customPixKeyType;

        const qrCode = this.generateEMVQRCode(payment);
        
        // Restaurar chave original
        this.pixKey = originalKey;
        this.pixKeyType = 'email';
        
        return {
            qrCode: qrCode,
            txid: payment.txid,
            amount: payment.amount,
            pixKey: customPixKey,
            expiresAt: payment.expires_at
        };
    }

    // =====================================================
    // FUNÇÕES AUXILIARES
    // =====================================================

    // Validar QR Code gerado
    validateQRCode(qrCode) {
        try {
            // Verificar se tem o formato básico
            if (!qrCode.startsWith('000201')) {
                return { valid: false, error: 'Formato inválido - deve começar com 000201' };
            }

            // Verificar se tem CRC16 válido
            const crcStart = qrCode.lastIndexOf('6304');
            if (crcStart === -1) {
                return { valid: false, error: 'CRC16 não encontrado' };
            }

            const dataPart = qrCode.substring(0, crcStart + 4);
            const crcPart = qrCode.substring(crcStart + 4);
            
            if (crcPart.length !== 4) {
                return { valid: false, error: 'CRC16 inválido' };
            }

            // Verificar CRC16
            const expectedCRC = this.calculateCRC16(dataPart);
            const actualCRC = parseInt(crcPart, 16);
            
            if (expectedCRC !== actualCRC) {
                return { valid: false, error: 'CRC16 inválido' };
            }

            return { valid: true, message: 'QR Code válido' };
        } catch (error) {
            return { valid: false, error: 'Erro ao validar QR Code: ' + error.message };
        }
    }

    // Decodificar QR Code (básico)
    decodeQRCode(qrCode) {
        try {
            const result = {
                payloadFormatIndicator: '',
                pointOfInitiationMethod: '',
                merchantAccountInformation: '',
                merchantCategoryCode: '',
                transactionCurrency: '',
                transactionAmount: '',
                countryCode: '',
                merchantName: '',
                merchantCity: '',
                additionalDataFieldTemplate: '',
                crc16: ''
            };

            let pos = 0;
            
            // Payload Format Indicator (00)
            if (qrCode.substring(pos, pos + 6) === '000201') {
                result.payloadFormatIndicator = '02';
                pos += 6;
            }

            // Point of Initiation Method (01)
            if (qrCode.substring(pos, pos + 6) === '010212') {
                result.pointOfInitiationMethod = '12';
                pos += 6;
            }

            // Merchant Account Information (26)
            if (qrCode.substring(pos, pos + 2) === '26') {
                pos += 2;
                const length = parseInt(qrCode.substring(pos, pos + 2));
                pos += 2;
                result.merchantAccountInformation = qrCode.substring(pos, pos + length);
                pos += length;
            }

            // Merchant Category Code (52)
            if (qrCode.substring(pos, pos + 6) === '52040000') {
                result.merchantCategoryCode = '0000';
                pos += 6;
            }

            // Transaction Currency (53)
            if (qrCode.substring(pos, pos + 6) === '5303986') {
                result.transactionCurrency = '986';
                pos += 6;
            }

            // Transaction Amount (54)
            if (qrCode.substring(pos, pos + 2) === '54') {
                pos += 2;
                const length = parseInt(qrCode.substring(pos, pos + 2));
                pos += 2;
                result.transactionAmount = qrCode.substring(pos, pos + length);
                pos += length;
            }

            // Country Code (58)
            if (qrCode.substring(pos, pos + 6) === '5802BR') {
                result.countryCode = 'BR';
                pos += 6;
            }

            // Merchant Name (59)
            if (qrCode.substring(pos, pos + 2) === '59') {
                pos += 2;
                const length = parseInt(qrCode.substring(pos, pos + 2));
                pos += 2;
                result.merchantName = qrCode.substring(pos, pos + length);
                pos += length;
            }

            // Merchant City (60)
            if (qrCode.substring(pos, pos + 2) === '60') {
                pos += 2;
                const length = parseInt(qrCode.substring(pos, pos + 2));
                pos += 2;
                result.merchantCity = qrCode.substring(pos, pos + length);
                pos += length;
            }

            // Additional Data Field Template (62)
            if (qrCode.substring(pos, pos + 2) === '62') {
                pos += 2;
                const length = parseInt(qrCode.substring(pos, pos + 2));
                pos += 2;
                result.additionalDataFieldTemplate = qrCode.substring(pos, pos + length);
                pos += length;
            }

            // CRC16 (63)
            if (qrCode.substring(pos, pos + 4) === '6304') {
                pos += 4;
                result.crc16 = qrCode.substring(pos, pos + 4);
            }

            return result;
        } catch (error) {
            return { error: 'Erro ao decodificar QR Code: ' + error.message };
        }
    }
}

// =====================================================
// EXEMPLOS DE USO
// =====================================================

// Inicializar gerador
const qrGenerator = new PixQRGenerator();

// Exemplo 1: QR Code simples
function generateSimpleQR() {
    const qrData = qrGenerator.generateSimplePixQR(50.00, 'Pagamento LacTech');
    console.log('QR Code simples:', qrData);
    
    // Validar QR Code
    const validation = qrGenerator.validateQRCode(qrData.qrCode);
    console.log('Validação:', validation);
    
    return qrData;
}

// Exemplo 2: QR Code baseado em cobrança do banco
function generateBankChargeQR() {
    const chargeData = {
        amount: 100.00,
        chargeId: 'COBRANCA_12345',
        merchantName: 'LacTech Sistema Leiteiro',
        merchantCity: 'SAO PAULO'
    };
    
    const qrData = qrGenerator.generateQRFromBankCharge(chargeData);
    console.log('QR Code da cobrança:', qrData);
    
    return qrData;
}

// Exemplo 3: QR Code com chave personalizada
function generateCustomPixQR() {
    const qrData = qrGenerator.generateCustomPixQR(25.00, '12345678901', 'cpf');
    console.log('QR Code personalizado:', qrData);
    
    return qrData;
}

// Exemplo 4: Decodificar QR Code
function decodeQRExample() {
    const qrCode = '00020126580014BR.GOV.BCB.PIX0136ebabf96f-5162-4bd1-95c5-64ffa8e9bfed52040000530398654041.005802BR5924Francisco Lavosier Silva6009SAO PAULO62140510d7tboHbbcE6304802C';
    
    const decoded = qrGenerator.decodeQRCode(qrCode);
    console.log('QR Code decodificado:', decoded);
    
    return decoded;
}

// =====================================================
// EXPORTAR PARA USO GLOBAL
// =====================================================

if (typeof window !== 'undefined') {
    window.PixQRGenerator = PixQRGenerator;
    window.qrGenerator = qrGenerator;
    
    // Funções de exemplo disponíveis globalmente
    window.generateSimpleQR = generateSimpleQR;
    window.generateBankChargeQR = generateBankChargeQR;
    window.generateCustomPixQR = generateCustomPixQR;
    window.decodeQRExample = decodeQRExample;
}

// =====================================================
// DOCUMENTAÇÃO DE USO
// =====================================================

/*
COMO USAR O GERADOR DE QR CODES PIX:

1. QR Code Simples:
   const qrData = qrGenerator.generateSimplePixQR(50.00, 'Descrição do pagamento');

2. QR Code baseado em cobrança do banco:
   const chargeData = {
       amount: 100.00,
       chargeId: 'COBRANCA_12345',
       merchantName: 'Nome da Empresa',
       merchantCity: 'CIDADE'
   };
   const qrData = qrGenerator.generateQRFromBankCharge(chargeData);

3. QR Code com chave personalizada:
   const qrData = qrGenerator.generateCustomPixQR(25.00, '12345678901', 'cpf');

4. Validar QR Code:
   const validation = qrGenerator.validateQRCode(qrData.qrCode);

5. Decodificar QR Code:
   const decoded = qrGenerator.decodeQRCode(qrCodeString);

FORMATO EMV GERADO:
- 000201: Payload Format Indicator (QR Code)
- 010212: Point of Initiation Method (Static)
- 26: Merchant Account Information
- 0014br.gov.bcb.pix: GUI (PIX)
- 01XXchave: PIX Key
- 52040000: Merchant Category Code
- 5303986: Transaction Currency (BRL)
- 54XXvalor: Transaction Amount
- 5802BR: Country Code
- 59XXnome: Merchant Name
- 60XXcidade: Merchant City
- 62: Additional Data Field Template
- 05XXtxid: Reference Label
- 6304XXXX: CRC16

EXEMPLO DE USO NO HTML:
<script src="pix_qr_generator.js"></script>
<script>
    // Gerar QR Code
    const qrData = qrGenerator.generateSimplePixQR(50.00);
    console.log('QR Code:', qrData.qrCode);
    
    // Exibir QR Code (requer biblioteca de QR Code)
    // qrcode.makeCode(qrData.qrCode);
</script>
*/
