<?php
/**
 * SafeNode - TOTP 2FA Implementation
 * Implementação de TOTP (Time-based One-Time Password) para autenticação de dois fatores
 * Compatível com Google Authenticator, Authy, Microsoft Authenticator, etc.
 */

class TOTP2FA
{
    private const ALGORITHM = 'sha1';
    private const DIGITS = 6;
    private const PERIOD = 30; // 30 segundos
    
    /**
     * Gerar chave secreta aleatória para 2FA
     */
    public static function generateSecretKey(): string
    {
        // Gerar 20 bytes (160 bits) de dados aleatórios
        $randomBytes = random_bytes(20);
        
        // Converter para Base32 (compatível com Google Authenticator)
        return self::base32Encode($randomBytes);
    }
    
    /**
     * Gerar código TOTP atual
     */
    public static function generateCode(string $secretKey): string
    {
        // Converter secret key de Base32 para binário
        $secret = self::base32Decode($secretKey);
        
        // Calcular time counter (número de períodos de 30 segundos desde epoch)
        $time = floor(time() / self::PERIOD);
        
        // Converter time para bytes (8 bytes, big-endian)
        $timeBytes = pack('N*', 0) . pack('N*', $time);
        
        // Calcular HMAC-SHA1
        $hmac = hash_hmac(self::ALGORITHM, $timeBytes, $secret, true);
        
        // Dynamic truncation
        $offset = ord($hmac[19]) & 0x0F;
        $code = (
            ((ord($hmac[$offset]) & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
            (ord($hmac[$offset + 3]) & 0xFF)
        ) % pow(10, self::DIGITS);
        
        // Garantir que tenha 6 dígitos (preencher com zeros à esquerda)
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }
    
    /**
     * Validar código TOTP (com tolerância de janela de tempo)
     * Aceita códigos de 6 ou 8 dígitos (alguns apps autenticadores podem gerar 8 dígitos)
     */
    public static function verifyCode(string $secretKey, string $code, int $window = 1): bool
    {
        $codeDigits = strlen($code);
        
        // Se o código não tem 6 ou 8 dígitos, não é válido
        if ($codeDigits !== 6 && $codeDigits !== 8) {
            return false;
        }
        
        // Usar o número de dígitos do código recebido para verificação
        $digitsToUse = $codeDigits;
        
        // Verificar código atual e códigos próximos (janela de tempo)
        for ($i = -$window; $i <= $window; $i++) {
            // Calcular time com offset
            $time = floor((time() + ($i * self::PERIOD)) / self::PERIOD);
            
            // Converter secret key
            $secret = self::base32Decode($secretKey);
            
            // Calcular código para este time
            $timeBytes = pack('N*', 0) . pack('N*', $time);
            $hmac = hash_hmac(self::ALGORITHM, $timeBytes, $secret, true);
            
            $offset = ord($hmac[19]) & 0x0F;
            $calculatedCode = (
                ((ord($hmac[$offset]) & 0x7F) << 24) |
                ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
                ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
                (ord($hmac[$offset + 3]) & 0xFF)
            ) % pow(10, $digitsToUse);
            
            $calculatedCode = str_pad((string)$calculatedCode, $digitsToUse, '0', STR_PAD_LEFT);
            
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Gerar URL para QR Code (compatível com Google Authenticator)
     */
    public static function getQRCodeUrl(string $secretKey, string $accountName, string $issuer = 'SafeNode'): string
    {
        $label = rawurlencode($accountName);
        $issuerEncoded = rawurlencode($issuer);
        
        return "otpauth://totp/{$label}?secret={$secretKey}&issuer={$issuerEncoded}&algorithm=" . strtoupper(self::ALGORITHM) . "&digits=" . self::DIGITS . "&period=" . self::PERIOD;
    }
    
    /**
     * Gerar códigos de backup
     */
    public static function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // Gerar código de 8 dígitos aleatório
            $code = str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $codes[] = $code;
        }
        return $codes;
    }
    
    /**
     * Codificar para Base32
     */
    private static function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $encoded = '';
        $bits = 0;
        $value = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $value = ($value << 8) | ord($data[$i]);
            $bits += 8;
            
            while ($bits >= 5) {
                $encoded .= $alphabet[($value >> ($bits - 5)) & 31];
                $bits -= 5;
            }
        }
        
        if ($bits > 0) {
            $encoded .= $alphabet[($value << (5 - $bits)) & 31];
        }
        
        return $encoded;
    }
    
    /**
     * Decodificar de Base32
     */
    private static function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper($data);
        $decoded = '';
        $bits = 0;
        $value = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            if ($char === ' ' || $char === '=') {
                continue;
            }
            
            $index = strpos($alphabet, $char);
            if ($index === false) {
                throw new Exception("Caractere inválido na chave Base32: {$char}");
            }
            
            $value = ($value << 5) | $index;
            $bits += 5;
            
            if ($bits >= 8) {
                $decoded .= chr(($value >> ($bits - 8)) & 255);
                $bits -= 8;
            }
        }
        
        return $decoded;
    }
}


