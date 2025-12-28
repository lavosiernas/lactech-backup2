<?php
/**
 * SafeNode - Two Factor Authentication Manager
 * Gerencia configuração e verificação de 2FA
 */

require_once __DIR__ . '/TOTP2FA.php';

class TwoFactorAuth
{
    private $db;
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    /**
     * Verificar se usuário tem 2FA ativado
     */
    public function isEnabled($userId): bool
    {
        try {
            $userId = (int)$userId;
            if ($userId <= 0) {
                return false;
            }
            
            $stmt = $this->db->prepare("SELECT is_enabled FROM safenode_user_2fa WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && isset($result['is_enabled']) && $result['is_enabled'] == 1;
        } catch (PDOException $e) {
            error_log("2FA Check Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Iniciar configuração de 2FA (gerar secret key)
     */
    public function startSetup($userId, $accountName)
    {
        try {
            $userId = (int)$userId;
            if ($userId <= 0) {
                return ['success' => false, 'error' => 'ID de usuário inválido'];
            }
            
            $secretKey = TOTP2FA::generateSecretKey();
            
            // Verificar se já existe registro
            $stmt = $this->db->prepare("SELECT id FROM safenode_user_2fa WHERE user_id = ?");
            $stmt->execute([$userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Atualizar secret key
                $stmt = $this->db->prepare("
                    UPDATE safenode_user_2fa 
                    SET secret_key = ?, is_enabled = 0, backup_codes = NULL, qr_code_setup_at = NULL 
                    WHERE user_id = ?
                ");
                $stmt->execute([$secretKey, $userId]);
            } else {
                // Criar novo registro
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_user_2fa (user_id, secret_key, is_enabled) 
                    VALUES (?, ?, 0)
                ");
                $stmt->execute([$userId, $secretKey]);
            }
            
            // Gerar backup codes (serão salvos após ativação)
            $backupCodes = TOTP2FA::generateBackupCodes();
            
            // Gerar QR Code URL
            $qrCodeUrl = TOTP2FA::getQRCodeUrl($secretKey, $accountName);
            
            return [
                'success' => true,
                'secret_key' => $secretKey,
                'qr_code_url' => $qrCodeUrl,
                'backup_codes' => $backupCodes
            ];
        } catch (PDOException $e) {
            error_log("2FA Setup Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao configurar 2FA'];
        }
    }
    
    /**
     * Verificar código e ativar 2FA
     */
    public function verifyAndActivate($userId, $code)
    {
        try {
            $userId = (int)$userId;
            if ($userId <= 0) {
                return ['success' => false, 'error' => 'ID de usuário inválido'];
            }
            
            $code = trim((string)$code);
            if (empty($code) || strlen($code) !== 6) {
                return ['success' => false, 'error' => 'Código deve ter 6 dígitos'];
            }
            
            // Buscar secret key
            $stmt = $this->db->prepare("SELECT secret_key FROM safenode_user_2fa WHERE user_id = ?");
            $stmt->execute([$userId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record || empty($record['secret_key'])) {
                return ['success' => false, 'error' => 'Configuração de 2FA não encontrada'];
            }
            
            $secretKey = $record['secret_key'];
            
            // Verificar código TOTP
            if (TOTP2FA::verifyCode($secretKey, $code)) {
                // Ativar 2FA e salvar backup codes
                $backupCodes = TOTP2FA::generateBackupCodes();
                // Garantir que todos os códigos sejam strings
                $backupCodes = array_map('strval', $backupCodes);
                $backupCodesJson = json_encode($backupCodes, JSON_UNESCAPED_UNICODE);
                
                $stmt = $this->db->prepare("
                    UPDATE safenode_user_2fa 
                    SET is_enabled = 1, backup_codes = ?, qr_code_setup_at = NOW() 
                    WHERE user_id = ?
                ");
                $stmt->execute([$backupCodesJson, $userId]);
                
                return [
                    'success' => true,
                    'backup_codes' => $backupCodes
                ];
            }
            
            return ['success' => false, 'error' => 'Código inválido'];
        } catch (PDOException $e) {
            error_log("2FA Verify Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao verificar código'];
        }
    }
    
    /**
     * Verificar código durante login
     */
    public function verifyLoginCode($userId, $code): bool
    {
        try {
            error_log("TwoFactorAuth::verifyLoginCode - START");
            error_log("  Input userId: " . var_export($userId, true));
            error_log("  Input code: " . var_export($code, true));
            
            // Validar userId
            $userId = (int)$userId;
            if ($userId <= 0) {
                error_log("  ERROR: Invalid userId after casting: $userId");
                return false;
            }
            error_log("  Validated userId: $userId");
            
            // Limpar e padronizar código
            $code = trim((string)$code);
            $codeLength = strlen($code);
            error_log("  Code after trim: '$code', Length: $codeLength");
            
            // Buscar secret key e backup codes
            $stmt = $this->db->prepare("SELECT secret_key, backup_codes FROM safenode_user_2fa WHERE user_id = ? AND is_enabled = 1");
            $stmt->execute([$userId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                error_log("  ERROR: No 2FA record found for user_id: $userId");
                return false;
            }
            
            if (empty($record['secret_key'])) {
                error_log("  ERROR: Empty secret_key for user_id: $userId");
                return false;
            }
            
            error_log("  Found 2FA record - has backup_codes: " . (!empty($record['backup_codes']) ? 'YES' : 'NO'));
            
            $secretKey = $record['secret_key'];
            
            // Se o código tem 8 dígitos, verificar backup codes primeiro
            if ($codeLength === 8 && !empty($record['backup_codes'])) {
                error_log("  Checking backup codes (8 digits)");
                $backupCodes = json_decode($record['backup_codes'], true);
                
                if (!$backupCodes || !is_array($backupCodes)) {
                    error_log("  ERROR: Failed to decode backup_codes JSON");
                } else {
                    error_log("  Backup codes count: " . count($backupCodes));
                    error_log("  Backup codes: " . json_encode($backupCodes));
                    
                    // Normalizar códigos para comparação
                    $normalizedCode = str_pad($code, 8, '0', STR_PAD_LEFT);
                    error_log("  Normalized input code: '$normalizedCode'");
                    
                    $codeFound = false;
                    $codeIndex = null;
                    
                    foreach ($backupCodes as $idx => $backupCode) {
                        $normalizedBackup = str_pad(trim((string)$backupCode), 8, '0', STR_PAD_LEFT);
                        error_log("    Comparing with backup[$idx]: '$normalizedBackup'");
                        if ($normalizedBackup === $normalizedCode) {
                            $codeFound = true;
                            $codeIndex = $idx;
                            error_log("  MATCH FOUND at index: $idx");
                            break;
                        }
                    }
                    
                    if ($codeFound && $codeIndex !== null) {
                        error_log("  Backup code verification SUCCESS");
                        // Remover código usado
                        unset($backupCodes[$codeIndex]);
                        $backupCodes = array_values($backupCodes);
                        $backupCodes = array_map('strval', $backupCodes);
                        $backupCodesJson = json_encode($backupCodes, JSON_UNESCAPED_UNICODE);
                        
                        $updateStmt = $this->db->prepare("UPDATE safenode_user_2fa SET backup_codes = ?, last_used_at = NOW() WHERE user_id = ?");
                        $updateStmt->execute([$backupCodesJson, $userId]);
                        
                        error_log("  Backup code removed and saved");
                        return true;
                    } else {
                        error_log("  Backup code NOT FOUND in list");
                    }
                }
            }
            
            // Verificar código TOTP (para 6 ou 8 dígitos)
            error_log("  Checking TOTP code");
            if (TOTP2FA::verifyCode($secretKey, $code)) {
                error_log("  TOTP verification SUCCESS");
                $updateStmt = $this->db->prepare("UPDATE safenode_user_2fa SET last_used_at = NOW() WHERE user_id = ?");
                $updateStmt->execute([$userId]);
                return true;
            } else {
                error_log("  TOTP verification FAILED");
            }
            
            error_log("  All verification methods FAILED");
            return false;
        } catch (PDOException $e) {
            error_log("2FA Verify Login Error: " . $e->getMessage());
            error_log("  Stack trace: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            error_log("2FA Verify Login General Error: " . $e->getMessage());
            error_log("  Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Desativar 2FA
     */
    public function disable($userId): bool
    {
        try {
            $userId = (int)$userId;
            if ($userId <= 0) {
                return false;
            }
            
            $stmt = $this->db->prepare("UPDATE safenode_user_2fa SET is_enabled = 0 WHERE user_id = ?");
            $stmt->execute([$userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("2FA Disable Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter backup codes do usuário
     */
    public function getBackupCodes($userId): array
    {
        try {
            $userId = (int)$userId;
            if ($userId <= 0) {
                return [];
            }
            
            $stmt = $this->db->prepare("SELECT backup_codes FROM safenode_user_2fa WHERE user_id = ? AND is_enabled = 1");
            $stmt->execute([$userId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($record && !empty($record['backup_codes'])) {
                $codes = json_decode($record['backup_codes'], true);
                return is_array($codes) ? $codes : [];
            }
            
            return [];
        } catch (PDOException $e) {
            error_log("2FA Get Backup Codes Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Gerar novos backup codes
     */
    public function regenerateBackupCodes($userId): array
    {
        try {
            $userId = (int)$userId;
            if ($userId <= 0) {
                return [];
            }
            
            $backupCodes = TOTP2FA::generateBackupCodes();
            // Garantir que todos os códigos sejam strings
            $backupCodes = array_map('strval', $backupCodes);
            $backupCodesJson = json_encode($backupCodes, JSON_UNESCAPED_UNICODE);
            
            $stmt = $this->db->prepare("UPDATE safenode_user_2fa SET backup_codes = ? WHERE user_id = ? AND is_enabled = 1");
            $stmt->execute([$backupCodesJson, $userId]);
            
            if ($stmt->rowCount() > 0) {
                return $backupCodes;
            }
            
            return [];
        } catch (PDOException $e) {
            error_log("2FA Regenerate Backup Codes Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter status do 2FA do usuário
     */
    public function getStatus($userId): array
    {
        try {
            $userId = (int)$userId;
            if ($userId <= 0) {
                return ['enabled' => false];
            }
            
            $stmt = $this->db->prepare("
                SELECT is_enabled, qr_code_setup_at, last_used_at 
                FROM safenode_user_2fa 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($record) {
                return [
                    'enabled' => isset($record['is_enabled']) && $record['is_enabled'] == 1,
                    'setup_at' => $record['qr_code_setup_at'] ?? null,
                    'last_used_at' => $record['last_used_at'] ?? null
                ];
            }
            
            return ['enabled' => false];
        } catch (PDOException $e) {
            error_log("2FA Get Status Error: " . $e->getMessage());
            return ['enabled' => false];
        }
    }
}


