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
    
    hv.init().then(() => {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            if (form.id) {
                hv.attachToForm('#' + form.id);
            } else {
                const tempId = 'safenode_form_' + Math.random().toString(36).substr(2, 9);
                form.id = tempId;
                hv.attachToForm('#' + tempId);
            }
            
            const submitHandler = async (e) => {
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                let originalText = '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    originalText = submitBtn.textContent || submitBtn.value || '';
                    if (submitBtn.textContent) submitBtn.textContent = 'Validando...';
                    if (submitBtn.value) submitBtn.value = 'Validando...';
                }
                try {
                    await hv.validateForm('#' + form.id);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                    form.removeEventListener('submit', submitHandler);
                    form.submit();
                } catch (error) {
                    console.error('SafeNode HV: Erro na validação:', error);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        if (submitBtn.textContent) submitBtn.textContent = originalText;
                        if (submitBtn.value) submitBtn.value = originalText;
                    }
                    alert('Verificação de segurança falhou. Por favor, tente novamente.');
                }
            };
            form.addEventListener('submit', submitHandler);
        });
    }).catch((error) => {
        console.error('SafeNode HV: Erro ao inicializar', error);
    });
})();
</script>
HTML;
    }

    /**
     * Obtém estatísticas de uso de uma API key
     */
    public static function getUsageStats(int $apiKeyId, int $userId, ?string $period = '24h'): array
    {
        $db = getSafeNodeDatabase();
        if (!$db) {
            return [];
        }

        try {
            // Validar que a API key pertence ao usuário
            $stmt = $db->prepare("SELECT id FROM safenode_hv_api_keys WHERE id = ? AND user_id = ?");
            $stmt->execute([$apiKeyId, $userId]);
            if (!$stmt->fetch()) {
                return [];
            }

            // Calcular intervalo de tempo
            $interval = match($period) {
                '1h' => '1 HOUR',
                '24h' => '24 HOUR',
                '7d' => '7 DAY',
                '30d' => '30 DAY',
                default => '24 HOUR'
            };

            // Total de requisições
            $stmt = $db->prepare("
                SELECT COUNT(*) as total
                FROM safenode_hv_attempts
                WHERE api_key_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
            ");
            $stmt->execute([$apiKeyId]);
            $total = (int)$stmt->fetchColumn();

            // Requisições por tipo
            $stmt = $db->prepare("
                SELECT attempt_type, COUNT(*) as count
                FROM safenode_hv_attempts
                WHERE api_key_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
                GROUP BY attempt_type
            ");
            $stmt->execute([$apiKeyId]);
            $byType = [];
            while ($row = $stmt->fetch()) {
                $byType[$row['attempt_type']] = (int)$row['count'];
            }

            // Taxa de sucesso
            $success = ($byType['init'] ?? 0) + ($byType['validate'] ?? 0);
            $failed = ($byType['failed'] ?? 0) + ($byType['suspicious'] ?? 0);
            $successRate = $total > 0 ? round(($success / $total) * 100, 2) : 0;

            // Requisições por hora (últimas 24h)
            $stmt = $db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
                    COUNT(*) as count,
                    SUM(CASE WHEN attempt_type IN ('init', 'validate') THEN 1 ELSE 0 END) as success,
                    SUM(CASE WHEN attempt_type IN ('failed', 'suspicious') THEN 1 ELSE 0 END) as failed
                FROM safenode_hv_attempts
                WHERE api_key_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY hour
                ORDER BY hour ASC
            ");
            $stmt->execute([$apiKeyId]);
            $hourly = [];
            while ($row = $stmt->fetch()) {
                $hourly[] = [
                    'hour' => $row['hour'],
                    'total' => (int)$row['count'],
                    'success' => (int)$row['success'],
                    'failed' => (int)$row['failed']
                ];
            }

            // IPs mais frequentes
            $stmt = $db->prepare("
                SELECT ip_address, COUNT(*) as count
                FROM safenode_hv_attempts
                WHERE api_key_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
                GROUP BY ip_address
                ORDER BY count DESC
                LIMIT 10
            ");
            $stmt->execute([$apiKeyId]);
            $topIPs = [];
            while ($row = $stmt->fetch()) {
                $topIPs[] = [
                    'ip' => $row['ip_address'],
                    'count' => (int)$row['count']
                ];
            }

            // Domínios mais frequentes (do referer)
            $stmt = $db->prepare("
                SELECT 
                    CASE 
                        WHEN referer IS NOT NULL AND referer != '' 
                        THEN SUBSTRING_INDEX(SUBSTRING_INDEX(referer, '://', -1), '/', 1)
                        ELSE 'Direct'
                    END as domain,
                    COUNT(*) as count
                FROM safenode_hv_attempts
                WHERE api_key_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
                GROUP BY domain
                ORDER BY count DESC
                LIMIT 10
            ");
            $stmt->execute([$apiKeyId]);
            $topDomains = [];
            while ($row = $stmt->fetch()) {
                $topDomains[] = [
                    'domain' => $row['domain'],
                    'count' => (int)$row['count']
                ];
            }

            return [
                'total' => $total,
                'by_type' => $byType,
                'success_rate' => $successRate,
                'success' => $success,
                'failed' => $failed,
                'hourly' => $hourly,
                'top_ips' => $topIPs,
                'top_domains' => $topDomains,
                'period' => $period
            ];
        } catch (PDOException $e) {
            error_log("HVAPIKeyManager::getUsageStats Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém estatísticas de desempenho (tempo de resposta médio, etc)
     */
    public static function getPerformanceStats(int $apiKeyId, int $userId, ?string $period = '24h'): array
    {
        $db = getSafeNodeDatabase();
        if (!$db) {
            return [];
        }

        try {
            // Validar que a API key pertence ao usuário
            $stmt = $db->prepare("SELECT id FROM safenode_hv_api_keys WHERE id = ? AND user_id = ?");
            $stmt->execute([$apiKeyId, $userId]);
            if (!$stmt->fetch()) {
                return [];
            }

            $interval = match($period) {
                '1h' => '1 HOUR',
                '24h' => '24 HOUR',
                '7d' => '7 DAY',
                '30d' => '30 DAY',
                default => '24 HOUR'
            };

            // Requisições por minuto (última hora)
            $stmt = $db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00') as minute,
                    COUNT(*) as count
                FROM safenode_hv_attempts
                WHERE api_key_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY minute
                ORDER BY minute ASC
            ");
            $stmt->execute([$apiKeyId]);
            $byMinute = [];
            while ($row = $stmt->fetch()) {
                $byMinute[] = [
                    'minute' => $row['minute'],
                    'count' => (int)$row['count']
                ];
            }

            // Pico de requisições
            $peak = 0;
            foreach ($byMinute as $min) {
                if ($min['count'] > $peak) {
                    $peak = $min['count'];
                }
            }

            // Distribuição por tipo de requisição
            $stmt = $db->prepare("
                SELECT 
                    attempt_type,
                    COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM safenode_hv_attempts WHERE api_key_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)), 2) as percentage
                FROM safenode_hv_attempts
                WHERE api_key_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
                GROUP BY attempt_type
            ");
            $stmt->execute([$apiKeyId, $apiKeyId]);
            $distribution = [];
            while ($row = $stmt->fetch()) {
                $distribution[] = [
                    'type' => $row['attempt_type'],
                    'count' => (int)$row['count'],
                    'percentage' => (float)$row['percentage']
                ];
            }

            return [
                'by_minute' => $byMinute,
                'peak_requests_per_minute' => $peak,
                'distribution' => $distribution,
                'period' => $period
            ];
        } catch (PDOException $e) {
            error_log("HVAPIKeyManager::getPerformanceStats Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém todas as estatísticas consolidadas
     */
    public static function getAllStats(int $apiKeyId, int $userId, ?string $period = '24h'): array
    {
        return [
            'usage' => self::getUsageStats($apiKeyId, $userId, $period),
            'performance' => self::getPerformanceStats($apiKeyId, $userId, $period)
        ];
    }
}

