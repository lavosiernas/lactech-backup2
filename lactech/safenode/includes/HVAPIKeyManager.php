<?php
/**
 * SafeNode - Gerenciador de API Keys para Verificação Humana
 */

class HVAPIKeyManager
{
    /**
     * Gera uma nova API key para o usuário
     */
    public static function generateKey(int $userId, string $name = 'Verificação Humana', ?string $allowedDomains = null, ?int $rateLimit = null, ?int $maxTokenAge = null): ?array
    {
        $db = getSafeNodeDatabase();
        if (!$db) {
            return null;
        }

        try {
            // Gerar API key e secret únicos
            $apiKey = 'sk_' . bin2hex(random_bytes(16));
            $apiSecret = bin2hex(random_bytes(32));

            // Valores padrão
            $rateLimit = $rateLimit ?? 60; // 60 requisições por minuto
            $maxTokenAge = $maxTokenAge ?? 3600; // 1 hora

            $stmt = $db->prepare("
                INSERT INTO safenode_hv_api_keys (user_id, api_key, api_secret, name, allowed_domains, rate_limit_per_minute, max_token_age)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $apiKey, $apiSecret, $name, $allowedDomains, $rateLimit, $maxTokenAge]);

            return [
                'id' => $db->lastInsertId(),
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'name' => $name,
                'allowed_domains' => $allowedDomains,
                'rate_limit_per_minute' => $rateLimit,
                'max_token_age' => $maxTokenAge
            ];
        } catch (PDOException $e) {
            error_log("HVAPIKeyManager::generateKey Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtém todas as API keys do usuário
     */
    public static function getUserKeys(int $userId): array
    {
        $db = getSafeNodeDatabase();
        if (!$db) {
            return [];
        }

        try {
            $stmt = $db->prepare("
                SELECT id, api_key, name, is_active, created_at, last_used_at, usage_count, 
                       allowed_domains, rate_limit_per_minute, max_token_age
                FROM safenode_hv_api_keys
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("HVAPIKeyManager::getUserKeys Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Valida uma API key e retorna os dados do usuário
     */
    public static function validateKey(string $apiKey, ?string $origin = null): ?array
    {
        $db = getSafeNodeDatabase();
        if (!$db) {
            return null;
        }

        try {
            $stmt = $db->prepare("
                SELECT k.*, u.id as user_id, u.username, u.email
                FROM safenode_hv_api_keys k
                INNER JOIN safenode_users u ON k.user_id = u.id
                WHERE k.api_key = ? AND k.is_active = 1
            ");
            $stmt->execute([$apiKey]);
            $key = $stmt->fetch();

            if (!$key) {
                return null;
            }

            // Validar domínio permitido se configurado
            if (!empty($key['allowed_domains']) && $origin) {
                $allowedDomains = array_map('trim', explode(',', $key['allowed_domains']));
                $originHost = parse_url($origin, PHP_URL_HOST);
                $isAllowed = false;
                
                foreach ($allowedDomains as $domain) {
                    if (empty($domain)) continue;
                    // Permitir domínio exato ou subdomínios
                    if ($originHost === $domain || 
                        $originHost === "www.$domain" ||
                        strpos($originHost, ".$domain") !== false) {
                        $isAllowed = true;
                        break;
                    }
                }
                
                if (!$isAllowed) {
                    self::logAttempt($key['id'], $_SERVER['REMOTE_ADDR'] ?? '', 
                        $_SERVER['HTTP_USER_AGENT'] ?? '', $origin, 'failed', 'Domínio não permitido');
                    return null;
                }
            }

            // Atualizar estatísticas
            $updateStmt = $db->prepare("
                UPDATE safenode_hv_api_keys
                SET last_used_at = NOW(), usage_count = usage_count + 1
                WHERE id = ?
            ");
            $updateStmt->execute([$key['id']]);

            return $key;
        } catch (PDOException $e) {
            error_log("HVAPIKeyManager::validateKey Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica rate limit para uma API key e IP
     */
    public static function checkRateLimit(int $apiKeyId, string $ipAddress): array
    {
        $db = getSafeNodeDatabase();
        if (!$db) {
            return ['allowed' => true, 'remaining' => 999];
        }

        try {
            // Buscar limite configurado
            $stmt = $db->prepare("
                SELECT rate_limit_per_minute 
                FROM safenode_hv_api_keys 
                WHERE id = ?
            ");
            $stmt->execute([$apiKeyId]);
            $key = $stmt->fetch();
            $limit = (int)($key['rate_limit_per_minute'] ?? 60);

            // Limpar registros antigos (mais de 1 minuto)
            $db->exec("
                DELETE FROM safenode_hv_rate_limits 
                WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            ");

            // Buscar ou criar registro de rate limit
            $windowStart = date('Y-m-d H:i:00'); // Arredondar para o minuto
            $stmt = $db->prepare("
                SELECT request_count 
                FROM safenode_hv_rate_limits 
                WHERE api_key_id = ? AND ip_address = ? AND window_start = ?
            ");
            $stmt->execute([$apiKeyId, $ipAddress, $windowStart]);
            $rateLimit = $stmt->fetch();

            if ($rateLimit) {
                $currentCount = (int)$rateLimit['request_count'];
                if ($currentCount >= $limit) {
                    return [
                        'allowed' => false,
                        'remaining' => 0,
                        'limit' => $limit,
                        'reset_at' => date('Y-m-d H:i:s', strtotime($windowStart . ' +1 minute'))
                    ];
                }
                // Incrementar contador
                $stmt = $db->prepare("
                    UPDATE safenode_hv_rate_limits 
                    SET request_count = request_count + 1 
                    WHERE api_key_id = ? AND ip_address = ? AND window_start = ?
                ");
                $stmt->execute([$apiKeyId, $ipAddress, $windowStart]);
                $remaining = $limit - $currentCount - 1;
            } else {
                // Criar novo registro
                $stmt = $db->prepare("
                    INSERT INTO safenode_hv_rate_limits (api_key_id, ip_address, window_start, request_count)
                    VALUES (?, ?, ?, 1)
                ");
                $stmt->execute([$apiKeyId, $ipAddress, $windowStart]);
                $remaining = $limit - 1;
            }

            return [
                'allowed' => true,
                'remaining' => max(0, $remaining),
                'limit' => $limit
            ];
        } catch (PDOException $e) {
            error_log("HVAPIKeyManager::checkRateLimit Error: " . $e->getMessage());
            return ['allowed' => true, 'remaining' => 999];
        }
    }

    /**
     * Registra tentativa (sucesso ou falha)
     */
    public static function logAttempt(?int $apiKeyId, string $ipAddress, ?string $userAgent, ?string $referer, string $type, ?string $reason = null): void
    {
        $db = getSafeNodeDatabase();
        if (!$db) {
            return;
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO safenode_hv_attempts 
                (api_key_id, ip_address, user_agent, referer, attempt_type, reason)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$apiKeyId, $ipAddress, $userAgent, $referer, $type, $reason]);
        } catch (PDOException $e) {
            error_log("HVAPIKeyManager::logAttempt Error: " . $e->getMessage());
        }
    }

    /**
     * Desativa uma API key
     */
    public static function deactivateKey(int $keyId, int $userId): bool
    {
        $db = getSafeNodeDatabase();
        if (!$db) {
            return false;
        }

        try {
            $stmt = $db->prepare("
                UPDATE safenode_hv_api_keys
                SET is_active = 0
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$keyId, $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("HVAPIKeyManager::deactivateKey Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reativa uma API key
     */
    public static function activateKey(int $keyId, int $userId): bool
    {
        $db = getSafeNodeDatabase();
        if (!$db) {
            return false;
        }

        try {
            $stmt = $db->prepare("
                UPDATE safenode_hv_api_keys
                SET is_active = 1
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$keyId, $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("HVAPIKeyManager::activateKey Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deleta uma API key
     */
    public static function deleteKey(int $keyId, int $userId): bool
    {
        $db = getSafeNodeDatabase();
        if (!$db) {
            return false;
        }

        try {
            $stmt = $db->prepare("
                DELETE FROM safenode_hv_api_keys
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$keyId, $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("HVAPIKeyManager::deleteKey Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gera o código HTML/JS personalizado para o usuário
     */
    public static function generateEmbedCode(string $apiKey, string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        
        // Detectar se está em produção ou desenvolvimento
        $isProduction = strpos($baseUrl, 'localhost') === false && 
                       strpos($baseUrl, '127.0.0.1') === false &&
                       strpos($baseUrl, '192.168.') === false;
        
        // O caminho do SDK sempre será relativo ao diretório safenode
        // Em produção: https://safenode.cloud/api/sdk (se safenode estiver na raiz)
        // Em desenvolvimento: http://localhost/.../lactech/safenode/api/sdk
        $apiUrl = $baseUrl . '/api/sdk';
        $sdkUrl = $baseUrl . '/sdk/safenode-hv.js';
        
        // Escapar para HTML
        $apiKeyEscaped = htmlspecialchars($apiKey, ENT_QUOTES);
        $apiUrlEscaped = htmlspecialchars($apiUrl, ENT_QUOTES);
        $sdkUrlEscaped = htmlspecialchars($sdkUrl, ENT_QUOTES);
        
        return <<<HTML
<!-- SafeNode Human Verification -->
<script src="{$sdkUrlEscaped}"></script>
<script>
(function() {
    const apiKey = '{$apiKeyEscaped}';
    const apiUrl = '{$apiUrlEscaped}';
    const hv = new SafeNodeHV(apiUrl, apiKey);
    
    // Inicializar
    hv.init().then(() => {
        // Adicionar campos aos formulários
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            // Adicionar campos hidden
            if (form.id) {
                hv.attachToForm('#' + form.id);
            } else {
                // Se não tem ID, criar um temporário
                const tempId = 'safenode_form_' + Math.random().toString(36).substr(2, 9);
                form.id = tempId;
                hv.attachToForm('#' + tempId);
            }
            
            // Validar antes de enviar
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    await hv.validateForm('#' + form.id);
                    form.submit();
                } catch (error) {
                    alert('Verificação de segurança falhou. Por favor, tente novamente.');
                }
            });
        });
    }).catch((error) => {
        console.error('SafeNode HV: Erro ao inicializar', error);
    });
})();
</script>
HTML;
    }
}

