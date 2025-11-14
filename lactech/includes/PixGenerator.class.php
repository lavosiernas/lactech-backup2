<?php
/**
 * Classe para gerar payload PIX (BR Code)
 * Implementação conforme especificação do Banco Central do Brasil
 */
class PixGenerator {
    private $pixKey;
    private $receiverName;
    private $receiverCity;
    
    public function __construct($pixKey, $receiverName = 'LacTech', $receiverCity = 'Brasilia') {
        $this->pixKey = trim($pixKey);
        $this->receiverName = substr(trim($receiverName), 0, 25);
        $this->receiverCity = substr(trim($receiverCity), 0, 15);
    }
    
    /**
     * Gera o payload PIX (BR Code) completo
     * 
     * @param string $txid Identificador único da transação
     * @param float $value Valor da cobrança
     * @param string $description Descrição opcional
     * @return string Payload PIX completo
     */
    public function generatePayload($txid, $value, $description = '') {
        // Validar dados
        if (empty($this->pixKey)) {
            throw new Exception('Chave PIX não pode estar vazia');
        }
        
        if (empty($txid) || strlen($txid) > 25) {
            throw new Exception('TXID inválido (máximo 25 caracteres)');
        }
        
        $numValue = floatval($value);
        if ($numValue <= 0) {
            throw new Exception('Valor deve ser maior que zero');
        }
        
        // 1. Payload Format Indicator (00) = 01
        $payload = '000201';
        
        // 2. Point of Initiation Method (01) = 12 (chave estática reutilizável)
        $payload .= '010212';
        
        // 3. Merchant Account Information (26)
        // Subcampo 00: GUI (Global Unique Identifier) = br.gov.bcb.pix
        $gui = '0014br.gov.bcb.pix';
        
        // Subcampo 01: Chave PIX
        $pixKeyLength = str_pad(strlen($this->pixKey), 2, '0', STR_PAD_LEFT);
        $pixKeyField = '01' . $pixKeyLength . $this->pixKey;
        
        // Comprimento total do campo 26
        $merchantAccountInfo = $gui . $pixKeyField;
        $merchantAccountInfoLength = str_pad(strlen($merchantAccountInfo), 2, '0', STR_PAD_LEFT);
        $payload .= '26' . $merchantAccountInfoLength . $merchantAccountInfo;
        
        // 4. Merchant Category Code (52) = 0000
        $payload .= '52040000';
        
        // 5. Transaction Currency (53) = 986 (BRL)
        $payload .= '5303986';
        
        // 6. Transaction Amount (54)
        $amount = number_format($numValue, 2, '.', '');
        $amountLength = str_pad(strlen($amount), 2, '0', STR_PAD_LEFT);
        $payload .= '54' . $amountLength . $amount;
        
        // 7. Country Code (58) = BR
        $payload .= '5802BR';
        
        // 8. Merchant Name (59) - máximo 25 caracteres
        $merchantNameLength = str_pad(strlen($this->receiverName), 2, '0', STR_PAD_LEFT);
        $payload .= '59' . $merchantNameLength . $this->receiverName;
        
        // 9. Merchant City (60) - máximo 15 caracteres
        $cityLength = str_pad(strlen($this->receiverCity), 2, '0', STR_PAD_LEFT);
        $payload .= '60' . $cityLength . $this->receiverCity;
        
        // 10. Additional Data Field Template (62)
        // Subcampo 05: Reference Label (TXID) - máximo 25 caracteres
        $txidFormatted = substr($txid, 0, 25);
        $txidLength = str_pad(strlen($txidFormatted), 2, '0', STR_PAD_LEFT);
        $txidField = '05' . $txidLength . $txidFormatted;
        $additionalDataLength = str_pad(strlen($txidField), 2, '0', STR_PAD_LEFT);
        $payload .= '62' . $additionalDataLength . $txidField;
        
        // 11. Calcular CRC16 sobre todo o payload (antes de adicionar o campo 63)
        $crc = $this->calculateCRC16($payload);
        $crcHex = strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
        
        // 12. Adicionar CRC16 (63) - sempre o último campo
        $payload .= '6304' . $crcHex;
        
        // Validar tamanho máximo (512 caracteres)
        if (strlen($payload) > 512) {
            throw new Exception('Payload PIX excede tamanho máximo (512 caracteres)');
        }
        
        return $payload;
    }
    
    /**
     * Calcula o CRC16 (padrão PIX - CRC-16/CCITT-FALSE)
     * 
     * @param string $str String para calcular o CRC16
     * @return int CRC16 calculado
     */
    private function calculateCRC16($str) {
        $polynomial = 0x1021;
        $crc = 0xFFFF;
        
        // Processar cada byte da string
        for ($i = 0; $i < strlen($str); $i++) {
            $byte = ord($str[$i]);
            $crc ^= ($byte << 8);
            
            for ($bit = 0; $bit < 8; $bit++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        
        return $crc & 0xFFFF;
    }
}
?>




















