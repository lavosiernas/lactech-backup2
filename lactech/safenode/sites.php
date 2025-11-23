<?php
/**
 * SafeNode - Gerenciamento de Sites
 * Interface similar ao Cloudflare para configurar sites protegidos
 */

session_start();

// SEGURANÇA: Carregar helpers e aplicar headers
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$db = getSafeNodeDatabase();

// Mensagens de sessão
$message = $_SESSION['safenode_message'] ?? '';
$messageType = $_SESSION['safenode_message_type'] ?? '';
unset($_SESSION['safenode_message']);
unset($_SESSION['safenode_message_type']);

// Criar tabela se não existir e atualizar schema
if ($db) {
    try {
        // Criação base
        $db->exec("CREATE TABLE IF NOT EXISTS safenode_sites (
            id INT PRIMARY KEY AUTO_INCREMENT,
            domain VARCHAR(255) NOT NULL,
            display_name VARCHAR(255) NULL,
            cloudflare_zone_id VARCHAR(100) NULL,
            cloudflare_status VARCHAR(50) DEFAULT 'active',
            ssl_status VARCHAR(50) DEFAULT 'pending',
            security_level VARCHAR(50) DEFAULT 'medium',
            auto_block BOOLEAN DEFAULT TRUE,
            rate_limit_enabled BOOLEAN DEFAULT TRUE,
            threat_detection_enabled BOOLEAN DEFAULT TRUE,
            is_active BOOLEAN DEFAULT TRUE,
            notes TEXT NULL,
            verification_token VARCHAR(64) NULL,
            verification_status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_domain (domain),
            INDEX idx_active (is_active),
            UNIQUE KEY unique_domain (domain)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Migrações (adicionar colunas se faltarem)
        $columns = $db->query("SHOW COLUMNS FROM safenode_sites")->fetchAll(PDO::FETCH_COLUMN);
        
        // CORREÇÃO DE SEGURANÇA: Adicionar user_id se não existir
        if (!in_array('user_id', $columns)) {
            $db->exec("ALTER TABLE safenode_sites ADD COLUMN user_id INT(11) NULL AFTER id");
            $db->exec("ALTER TABLE safenode_sites ADD INDEX idx_user_id (user_id)");
        }
        
        if (!in_array('verification_token', $columns)) {
            $db->exec("ALTER TABLE safenode_sites ADD COLUMN verification_token VARCHAR(64) NULL AFTER notes");
        }
        if (!in_array('verification_status', $columns)) {
            $db->exec("ALTER TABLE safenode_sites ADD COLUMN verification_status VARCHAR(20) DEFAULT 'pending' AFTER verification_token");
        }
        if (!in_array('geo_allow_only', $columns)) {
            $db->exec("ALTER TABLE safenode_sites ADD COLUMN geo_allow_only TINYINT(1) DEFAULT 0 AFTER verification_status");
        }

        $db->exec("CREATE TABLE IF NOT EXISTS safenode_site_geo_rules (
            id INT PRIMARY KEY AUTO_INCREMENT,
            site_id INT NOT NULL,
            country_code CHAR(2) NOT NULL,
            action ENUM('block','allow') DEFAULT 'block',
            notes VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY site_country (site_id, country_code),
            CONSTRAINT fk_geo_site FOREIGN KEY (site_id) REFERENCES safenode_sites(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    } catch (PDOException $e) {
        error_log("SafeNode Sites Table Error: " . $e->getMessage());
    }
}

// Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_site'])) {
        $domain = trim($_POST['domain'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $cloudflareZoneId = trim($_POST['cloudflare_zone_id'] ?? '');
        $securityLevel = $_POST['security_level'] ?? 'medium';
        
        // Validação de domínio
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/^www\./', '', $domain);
        $domain = rtrim($domain, '/');
        
        if (!empty($domain) && $db) {
            // Validação básica de formato de domínio
            if (!preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $domain)) {
                $message = "Formato de domínio inválido!";
                $messageType = "error";
            } else {
                try {
                    // Gerar token de verificação
                    $verificationToken = bin2hex(random_bytes(32));
                    
                    // SEGURANÇA: Sempre associar site ao usuário logado
                    $userId = $_SESSION['safenode_user_id'] ?? null;
                    if (!$userId) {
                        throw new Exception("Usuário não identificado");
                    }

                    $stmt = $db->prepare("INSERT INTO safenode_sites (user_id, domain, display_name, cloudflare_zone_id, security_level, verification_token, verification_status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->execute([$userId, $domain, $displayName ?: null, $cloudflareZoneId ?: null, $securityLevel, $verificationToken]);
                    $_SESSION['safenode_message'] = "Site adicionado! Verifique o domínio para ativar a proteção completa.";
                    $_SESSION['safenode_message_type'] = "success";
                    header('Location: sites.php');
                    exit;
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $message = "Este domínio já está cadastrado!";
                        $messageType = "error";
                    } else {
                        $message = "Erro ao adicionar site: " . $e->getMessage();
                        $messageType = "error";
                    }
                }
            }
        } elseif (empty($domain)) {
            $message = "O domínio é obrigatório!";
            $messageType = "error";
        }
    } elseif (isset($_POST['verify_site'])) {
        $siteId = intval($_POST['site_id'] ?? 0);
        if ($siteId > 0 && $db) {
            // SEGURANÇA: Verificar que o site pertence ao usuário logado
            $userId = $_SESSION['safenode_user_id'] ?? null;
            $stmt = $db->prepare("SELECT * FROM safenode_sites WHERE id = ? AND user_id = ?");
            $stmt->execute([$siteId, $userId]);
            $site = $stmt->fetch();
            
            if ($site) {
                $domain = $site['domain'];
                $token = $site['verification_token'];
                $verified = false;
                $verificationError = '';
                $foundRecords = [];

                // Método 1: DNS TXT - Tentar múltiplas vezes para aguardar propagação
                $maxAttempts = 3;
                $attempt = 0;
                
                while (!$verified && $attempt < $maxAttempts) {
                    // Tentar verificação DNS múltiplas vezes para lidar com propagação
                    $dnsRecords = @dns_get_record($domain, DNS_TXT);
                    
                    if ($dnsRecords === false) {
                        $verificationError = "Não foi possível consultar o DNS do domínio. Verifique se o domínio está configurado corretamente.";
                        $attempt++;
                        if ($attempt < $maxAttempts) {
                            sleep(3);
                        }
                        continue;
                    }
                    
                    if ($dnsRecords && is_array($dnsRecords)) {
                        // Armazenar todos os registros TXT encontrados para debug
                        foreach ($dnsRecords as $record) {
                            if (isset($record['txt'])) {
                                $foundRecords[] = $record['txt'];
                            }
                        }
                        
                        foreach ($dnsRecords as $record) {
                            $txtValue = $record['txt'] ?? '';
                            
                            if (empty($txtValue)) {
                                continue;
                            }
                            
                            // Verificar se o registro TXT contém o token de verificação completo
                            $expectedValue = "safenode-verification=$token";
                            if (strpos($txtValue, $expectedValue) !== false) {
                                $verified = true;
                                break 2; // Sair de ambos os loops
                            }
                            
                            // Também verificar se o valor completo corresponde exatamente
                            if (trim($txtValue) === $expectedValue) {
                                $verified = true;
                                break 2;
                            }
                            
                            // Verificar se o valor é só o token (caso o usuário tenha adicionado só o token)
                            if (trim($txtValue) === $token) {
                                $verified = true;
                                break 2;
                            }
                        }
                    }
                    
                    // Se não encontrou e ainda há tentativas, aguardar um pouco antes de tentar novamente
                    if (!$verified && $attempt < $maxAttempts - 1) {
                        sleep(5); // Aguardar 5 segundos entre tentativas para dar tempo de propagação
                    }
                    
                    $attempt++;
                }
                
                // Se não verificou, preparar mensagem de erro detalhada
                if (!$verified) {
                    if (empty($foundRecords)) {
                        $verificationError = "Nenhum registro TXT encontrado para o domínio. Verifique se você adicionou o registro DNS corretamente.";
                    } else {
                        $recordsList = implode(', ', array_slice($foundRecords, 0, 3));
                        $verificationError = "Registro TXT não encontrado. Registros TXT encontrados: $recordsList. ";
                        $verificationError .= "O registro esperado é: safenode-verification=$token";
                    }
                }

                // Método 2: Arquivo HTTP (se DNS falhar)
                if (!$verified) {
                    // Tentar HTTP e HTTPS
                    $urls = [
                        "http://$domain/safenode-verification.txt",
                        "https://$domain/safenode-verification.txt"
                    ];
                    
                    foreach ($urls as $url) {
                        $ctx = stream_context_create([
                            'http' => [
                                'timeout' => 10,
                                'user_agent' => 'SafeNode-Verification/1.0',
                                'follow_location' => 1,
                                'max_redirects' => 3
                            ]
                        ]);
                        $content = @file_get_contents($url, false, $ctx);
                        if ($content && trim($content) === $token) {
                            $verified = true;
                            break;
                        }
                    }
                }

                if ($verified) {
                    $db->prepare("UPDATE safenode_sites SET verification_status = 'verified' WHERE id = ?")->execute([$siteId]);
                    $_SESSION['safenode_message'] = "Domínio verificado com sucesso! ✅";
                    $_SESSION['safenode_message_type'] = "success";
                } else {
                    // Usar mensagem de erro detalhada se disponível
                    $errorMessage = !empty($verificationError) 
                        ? $verificationError 
                        : "Não foi possível verificar o domínio. Verifique o registro DNS ou arquivo e tente novamente.";
                    
                    // Adicionar dica útil
                    $errorMessage .= " 💡 Dica: Após adicionar o registro DNS na Cloudflare, pode levar alguns minutos para propagar. Aguarde 2-5 minutos e tente novamente.";
                    
                    $_SESSION['safenode_message'] = $errorMessage;
                    $_SESSION['safenode_message_type'] = "error";
                    
                    // Log para debug
                    error_log("SafeNode Domain Verification Failed - Domain: $domain, Token: $token, Found TXT Records: " . json_encode($foundRecords));
                }
                header('Location: sites.php');
                exit;
            }
        }
    } elseif (isset($_POST['add_geo_rule'])) {
        $siteId = intval($_POST['site_id'] ?? 0);
        $countryCode = strtoupper(trim($_POST['country_code'] ?? ''));
        $geoAction = $_POST['geo_action'] === 'allow' ? 'allow' : 'block';

        if ($siteId > 0 && preg_match('/^[A-Z]{2}$/', $countryCode) && $db) {
            try {
                $stmt = $db->prepare("INSERT INTO safenode_site_geo_rules (site_id, country_code, action) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE action = VALUES(action)");
                $stmt->execute([$siteId, $countryCode, $geoAction]);
                $_SESSION['safenode_message'] = "Regra geográfica salva para {$countryCode}.";
                $_SESSION['safenode_message_type'] = "success";
            } catch (PDOException $e) {
                $_SESSION['safenode_message'] = "Erro ao salvar regra: " . $e->getMessage();
                $_SESSION['safenode_message_type'] = "error";
            }
        } else {
            $_SESSION['safenode_message'] = "Informe um código de país válido (ex: BR, US).";
            $_SESSION['safenode_message_type'] = "error";
        }
        header('Location: sites.php');
        exit;
    } elseif (isset($_POST['delete_geo_rule'])) {
        $ruleId = intval($_POST['rule_id'] ?? 0);
        if ($ruleId > 0 && $db) {
            try {
                $stmt = $db->prepare("DELETE FROM safenode_site_geo_rules WHERE id = ?");
                $stmt->execute([$ruleId]);
                $_SESSION['safenode_message'] = "Regra de geolocalização removida.";
                $_SESSION['safenode_message_type'] = "success";
            } catch (PDOException $e) {
                $_SESSION['safenode_message'] = "Erro ao remover regra: " . $e->getMessage();
                $_SESSION['safenode_message_type'] = "error";
            }
        }
        header('Location: sites.php');
        exit;
    } elseif (isset($_POST['add_fw_rule'])) {
        $siteId = intval($_POST['site_id'] ?? 0);
        $matchType = $_POST['match_type'] ?? '';
        $matchValue = trim($_POST['match_value'] ?? '');
        $fwAction = in_array($_POST['fw_action'] ?? '', ['block', 'allow', 'log'], true) ? $_POST['fw_action'] : 'block';
        $priority = intval($_POST['priority'] ?? 0);

        $allowedTypes = ['path_prefix', 'ip', 'country', 'user_agent'];

        if ($siteId > 0 && $db && in_array($matchType, $allowedTypes, true) && $matchValue !== '') {
            try {
                $stmt = $db->prepare("INSERT INTO safenode_firewall_rules (site_id, priority, match_type, match_value, action, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$siteId, $priority, $matchType, $matchValue, $fwAction]);
                $_SESSION['safenode_message'] = "Regra de firewall adicionada.";
                $_SESSION['safenode_message_type'] = "success";
            } catch (PDOException $e) {
                $_SESSION['safenode_message'] = "Erro ao adicionar regra: " . $e->getMessage();
                $_SESSION['safenode_message_type'] = "error";
            }
        } else {
            $_SESSION['safenode_message'] = "Preencha corretamente tipo e valor da regra.";
            $_SESSION['safenode_message_type'] = "error";
        }
        header('Location: sites.php');
        exit;
    } elseif (isset($_POST['delete_fw_rule'])) {
        $ruleId = intval($_POST['rule_id'] ?? 0);
        if ($ruleId > 0 && $db) {
            try {
                $stmt = $db->prepare("DELETE FROM safenode_firewall_rules WHERE id = ?");
                $stmt->execute([$ruleId]);
                $_SESSION['safenode_message'] = "Regra de firewall removida.";
                $_SESSION['safenode_message_type'] = "success";
            } catch (PDOException $e) {
                $_SESSION['safenode_message'] = "Erro ao remover regra: " . $e->getMessage();
                $_SESSION['safenode_message_type'] = "error";
            }
        }
        header('Location: sites.php');
        exit;
    } elseif (isset($_POST['toggle_geo_allow_only'])) {
        $siteId = intval($_POST['site_id'] ?? 0);
        if ($siteId > 0 && $db) {
            try {
                $stmt = $db->prepare("UPDATE safenode_sites SET geo_allow_only = IFNULL(1 - geo_allow_only, 1) WHERE id = ?");
                $stmt->execute([$siteId]);
                $_SESSION['safenode_message'] = "Modo \"permitir somente países listados\" atualizado.";
                $_SESSION['safenode_message_type'] = "success";
            } catch (PDOException $e) {
                $_SESSION['safenode_message'] = "Erro ao atualizar modo: " . $e->getMessage();
                $_SESSION['safenode_message_type'] = "error";
            }
        }
        header('Location: sites.php');
        exit;
    } elseif (isset($_POST['update_site'])) {
        $siteId = intval($_POST['site_id'] ?? 0);
        $displayName = trim($_POST['display_name'] ?? '');
        $cloudflareZoneId = trim($_POST['cloudflare_zone_id'] ?? '');
        $securityLevel = $_POST['security_level'] ?? 'medium';
        $autoBlock = isset($_POST['auto_block']) ? 1 : 0;
        $rateLimit = isset($_POST['rate_limit_enabled']) ? 1 : 0;
        $threatDetection = isset($_POST['threat_detection_enabled']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if ($siteId > 0 && $db) {
            try {
                $stmt = $db->prepare("UPDATE safenode_sites SET display_name = ?, cloudflare_zone_id = ?, security_level = ?, auto_block = ?, rate_limit_enabled = ?, threat_detection_enabled = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$displayName ?: null, $cloudflareZoneId ?: null, $securityLevel, $autoBlock, $rateLimit, $threatDetection, $isActive, $siteId]);
                $_SESSION['safenode_message'] = "Site atualizado com sucesso!";
                $_SESSION['safenode_message_type'] = "success";
                header('Location: sites.php');
                exit;
            } catch (PDOException $e) {
                $message = "Erro ao atualizar site: " . $e->getMessage();
                $messageType = "error";
            }
        }
    } elseif (isset($_POST['delete_site'])) {
        $siteId = intval($_POST['site_id'] ?? 0);
        
        if ($siteId > 0 && $db) {
            try {
                $stmt = $db->prepare("DELETE FROM safenode_sites WHERE id = ?");
                $stmt->execute([$siteId]);
                $_SESSION['safenode_message'] = "Site removido com sucesso!";
                $_SESSION['safenode_message_type'] = "success";
                header('Location: sites.php');
                exit;
            } catch (PDOException $e) {
                $message = "Erro ao remover site: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

// Buscar sites
$sites = [];

if ($db) {
    try {
        // SEGURANÇA: Mostrar apenas sites do usuário logado
        $userId = $_SESSION['safenode_user_id'] ?? null;
        $stmt = $db->prepare("SELECT * FROM safenode_sites WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $sites = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("SafeNode Sites Error: " . $e->getMessage());
    }
}

// Estatísticas por site
$siteStats = [];
$siteGeoRules = [];
$siteFirewallRules = [];
foreach ($sites as $site) {
    if ($db) {
        try {
            // Estatísticas das últimas 24 horas
            $stmt = $db->prepare("SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked,
                COUNT(DISTINCT ip_address) as unique_ips,
                SUM(CASE WHEN threat_score >= 70 THEN 1 ELSE 0 END) as high_threats
                FROM safenode_security_logs 
                WHERE (request_uri LIKE ? OR request_uri LIKE ?) 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $domainPattern = '%' . $site['domain'] . '%';
            $stmt->execute([$domainPattern, 'http://' . $site['domain'] . '%']);
            $result = $stmt->fetch();
            $siteStats[$site['id']] = $result ?: ['total_requests' => 0, 'blocked' => 0, 'unique_ips' => 0, 'high_threats' => 0];
        } catch (PDOException $e) {
            error_log("SafeNode Stats Error: " . $e->getMessage());
            $siteStats[$site['id']] = ['total_requests' => 0, 'blocked' => 0, 'unique_ips' => 0, 'high_threats' => 0];
        }

        try {
            $stmt = $db->prepare("SELECT * FROM safenode_site_geo_rules WHERE site_id = ? ORDER BY country_code");
            $stmt->execute([$site['id']]);
            $siteGeoRules[$site['id']] = $stmt->fetchAll();
        } catch (PDOException $e) {
            $siteGeoRules[$site['id']] = [];
        }

        try {
            $stmt = $db->prepare("SELECT * FROM safenode_firewall_rules WHERE site_id = ? ORDER BY priority DESC, id ASC");
            $stmt->execute([$site['id']]);
            $siteFirewallRules[$site['id']] = $stmt->fetchAll();
        } catch (PDOException $e) {
            $siteFirewallRules[$site['id']] = [];
        }
    } else {
        $siteStats[$site['id']] = ['total_requests' => 0, 'blocked' => 0, 'unique_ips' => 0, 'high_threats' => 0];
        $siteGeoRules[$site['id']] = [];
        $siteFirewallRules[$site['id']] = [];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sites | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] } } } }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }
        .glass-card { background: linear-gradient(180deg, rgba(39, 39, 42, 0.4) 0%, rgba(24, 24, 27, 0.4) 100%); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.05); }
        
        /* Melhorar quebra de linha para chaves longas */
        #verify_token_dns, #verify_token_file {
            word-break: break-all;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            hyphens: auto;
            line-height: 1.6;
        }
        
        /* Scroll horizontal suave quando necessário */
        #verifyModal code {
            word-break: break-all;
            overflow-wrap: break-word;
            max-width: 100%;
        }
        
        #verifyModal .overflow-x-auto {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #3f3f46 transparent;
        }
        
        /* Forçar quebra de palavras longas */
        .word-break-all {
            word-break: break-all !important;
            overflow-wrap: anywhere !important;
            word-wrap: break-word !important;
        }
        
        /* Modal responsivo */
        @media (max-width: 640px) {
            #verifyModal {
                padding: 0.5rem;
            }
            
            #verifyModal .glass-card {
                max-height: calc(100vh - 1rem);
                overflow-y: auto;
                padding: 1rem;
            }
            
            #verify_token_dns, #verify_token_file {
                font-size: 0.625rem;
                line-height: 1.5;
            }
        }
        
        @media (max-width: 480px) {
            #verify_token_dns, #verify_token_file {
                font-size: 0.5rem;
            }
        }
    </style>
</head>
<body class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
            <div class="flex items-center gap-4 md:hidden">
                <button class="text-zinc-400 hover:text-white" data-sidebar-toggle>
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-lg text-white">SafeNode</span>
            </div>
            <div class="hidden md:block">
                <h2 class="text-xl font-bold text-white tracking-tight">Gerenciar Sites</h2>
                <p class="text-xs text-zinc-400 mt-0.5">Configure e monitore seus domínios protegidos</p>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10">
            <div class="max-w-7xl mx-auto space-y-6">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'; ?> font-semibold flex items-center gap-2">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="glass-card rounded-xl p-6 mb-6">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-white mb-2">Adicionar Novo Site</h3>
                    <p class="text-sm text-zinc-400">Configure um novo domínio para proteção com SafeNode. O sistema irá monitorar e proteger automaticamente contra ameaças.</p>
                </div>
                
                <form method="POST" class="space-y-5">
                    <input type="hidden" name="add_site" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                Domínio <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="domain" required placeholder="exemplo.com" pattern="^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <p class="mt-1.5 text-xs text-zinc-500">Digite o domínio sem http:// ou https:// (ex: meusite.com.br)</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                Nome de Exibição
                            </label>
                            <input type="text" name="display_name" placeholder="Meu Site Principal" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <p class="mt-1.5 text-xs text-zinc-500">Nome amigável para identificar o site no painel</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                Cloudflare Zone ID
                            </label>
                            <input type="text" name="cloudflare_zone_id" placeholder="abc123def456..." class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <p class="mt-1.5 text-xs text-zinc-500">Opcional: ID da zona no Cloudflare para sincronização automática</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                Nível de Segurança
                            </label>
                            <select name="security_level" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <option value="low">Baixo - Proteção básica</option>
                                <option value="medium" selected>Médio - Proteção recomendada</option>
                                <option value="high">Alto - Máxima proteção</option>
                                <option value="under_attack">Sob Ataque - Modo de emergência</option>
                            </select>
                            <p class="mt-1.5 text-xs text-zinc-500">Define o nível de rigor na detecção e bloqueio de ameaças</p>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-white/5">
                        <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-4">
                            <div class="flex items-start gap-3">
                                <i data-lucide="info" class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                <div>
                                    <h4 class="text-sm font-semibold text-blue-400 mb-1">Como funciona?</h4>
                                    <ul class="text-xs text-zinc-400 space-y-1">
                                        <li>• O SafeNode monitora todas as requisições ao domínio configurado</li>
                                        <li>• Detecta automaticamente ameaças como SQL Injection, XSS, Brute Force, etc.</li>
                                        <li>• Bloqueia IPs maliciosos automaticamente conforme o nível de segurança</li>
                                        <li>• Integra com Cloudflare para sincronização de bloqueios (se configurado)</li>
                                        <li>• Gera logs detalhados de todas as atividades de segurança</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all shadow-lg shadow-blue-500/20 flex items-center gap-2">
                                <i data-lucide="plus" class="w-5 h-5"></i>
                                Adicionar Site
                            </button>
                            <button type="reset" class="px-6 py-2.5 bg-zinc-800 text-zinc-300 rounded-xl hover:bg-zinc-700 font-semibold transition-all">
                                Limpar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php if (empty($sites)): ?>
                    <div class="col-span-full">
                        <div class="glass-card rounded-xl p-12 text-center">
                            <div class="w-16 h-16 bg-zinc-900 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="globe" class="w-8 h-8 text-zinc-500"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2">Nenhum site configurado</h3>
                            <p class="text-sm text-zinc-400">Use o formulário acima para adicionar seu primeiro site</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($sites as $site): ?>
                        <?php 
                        $stats = $siteStats[$site['id']] ?? ['total_requests' => 0, 'blocked' => 0, 'unique_ips' => 0, 'high_threats' => 0];
                        $blockPercentage = $stats['total_requests'] > 0 ? round(($stats['blocked'] / $stats['total_requests']) * 100, 1) : 0;
                        ?>
                        <div class="glass-card rounded-xl p-6 hover:border-blue-500/30 transition-all flex flex-col gap-4">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-lg font-bold text-white">
                                            <?php echo htmlspecialchars($site['display_name'] ?: $site['domain']); ?>
                                        </h3>
                                        <?php if ($site['is_active']): ?>
                                            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse" title="Site Ativo"></span>
                                        <?php else: ?>
                                            <span class="w-2 h-2 bg-zinc-500 rounded-full" title="Site Inativo"></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-zinc-400 font-mono"><?php echo htmlspecialchars($site['domain']); ?></p>
                                    
                                    <!-- Verification Status -->
                                    <?php if (!isset($site['verification_status']) || $site['verification_status'] !== 'verified'): ?>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="text-xs text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">
                                                Não Verificado
                                            </span>
                                            <button type="button" onclick="openVerifyModal(<?php echo htmlspecialchars(json_encode($site), ENT_QUOTES, 'UTF-8'); ?>)" class="text-xs text-blue-400 hover:underline cursor-pointer">
                                                Verificar Agora
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-2">
                                            <span class="text-xs text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20 flex items-center gap-1 w-fit">
                                                <i data-lucide="check" class="w-3 h-3"></i> Verificado
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($site['cloudflare_zone_id']): ?>
                                        <?php
                                        // Verificar se API Token está configurado
                                        $hasApiToken = false;
                                        if (!empty($site['cloudflare_api_token'])) {
                                            $hasApiToken = true;
                                        } else {
                                            $stmt = $db->prepare("SELECT setting_value FROM safenode_settings WHERE setting_key = 'cloudflare_api_token' LIMIT 1");
                                            $stmt->execute();
                                            $tokenSetting = $stmt->fetch();
                                            $hasApiToken = !empty($tokenSetting['setting_value']);
                                        }
                                        ?>
                                        <p class="text-xs text-zinc-500 mt-2">
                                            <i data-lucide="cloud" class="w-3 h-3 inline"></i>
                                            Cloudflare conectado
                                        </p>
                                        <?php if ($hasApiToken): ?>
                                            <a href="dns_records.php?id=<?php echo $site['id']; ?>" class="text-xs text-blue-400 hover:underline ml-4">
                                                Gerenciar DNS
                                            </a>
                                        <?php else: ?>
                                            <span class="text-xs text-amber-400 ml-4" title="Configure o API Token do Cloudflare em Configurações → Cloudflare">
                                                API Token não configurado
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Status Badges -->
                        <div class="flex flex-wrap gap-2 mb-4">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg <?php 
                                    echo $site['cloudflare_status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 
                                        ($site['cloudflare_status'] === 'pending' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-zinc-800 text-zinc-400 border border-white/5');
                                ?>">
                                    <i data-lucide="shield" class="w-3 h-3 mr-1"></i>
                                    Cloudflare: <?php echo ucfirst($site['cloudflare_status']); ?>
                                </span>
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg <?php 
                                    echo $site['security_level'] === 'high' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 
                                        ($site['security_level'] === 'medium' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 
                                        ($site['security_level'] === 'under_attack' ? 'bg-red-600/20 text-red-300 border border-red-500/30' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20'));
                                ?>">
                                    <i data-lucide="lock" class="w-3 h-3 mr-1"></i>
                                    <?php 
                                        $levelNames = [
                                            'low' => 'Baixo',
                                            'medium' => 'Médio',
                                            'high' => 'Alto',
                                            'under_attack' => 'Sob Ataque'
                                        ];
                                        echo $levelNames[$site['security_level']] ?? ucfirst($site['security_level']);
                                    ?>
                                </span>
                            </div>

                            <!-- Estatísticas 24h -->
                            <div class="mb-4 pt-4 border-t border-white/5">
                            <div class="grid grid-cols-3 gap-3 mb-3 text-center">
                                    <div>
                                        <p class="text-xs text-zinc-500 mb-1">Requisições</p>
                                        <p class="text-lg font-bold text-white"><?php echo number_format($stats['total_requests']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-500 mb-1">Bloqueados</p>
                                        <p class="text-lg font-bold text-red-400"><?php echo number_format($stats['blocked']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-500 mb-1">IPs Únicos</p>
                                        <p class="text-lg font-bold text-white"><?php echo number_format($stats['unique_ips']); ?></p>
                                    </div>
                                </div>
                                <?php if ($stats['total_requests'] > 0): ?>
                                    <div class="mt-3 pt-3 border-t border-white/5">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs text-zinc-500">Taxa de Bloqueio</span>
                                            <span class="text-xs font-semibold <?php echo $blockPercentage > 10 ? 'text-red-400' : ($blockPercentage > 5 ? 'text-amber-400' : 'text-emerald-400'); ?>">
                                                <?php echo $blockPercentage; ?>%
                                            </span>
                                        </div>
                                        <div class="w-full h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                                            <div class="h-full <?php echo $blockPercentage > 10 ? 'bg-red-500' : ($blockPercentage > 5 ? 'bg-amber-500' : 'bg-emerald-500'); ?>" style="width: <?php echo min(100, $blockPercentage); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($stats['high_threats'] > 0): ?>
                                    <div class="mt-2 flex items-center gap-2 text-xs">
                                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-400"></i>
                                        <span class="text-red-400 font-semibold"><?php echo number_format($stats['high_threats']); ?> ameaças críticas</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Features -->
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php if ($site['auto_block']): ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-emerald-500/10 text-emerald-400 rounded-lg font-medium border border-emerald-500/20">
                                        <i data-lucide="shield-check" class="w-3 h-3 mr-1"></i>
                                        Auto-Block
                                    </span>
                                <?php endif; ?>
                                <?php if ($site['rate_limit_enabled']): ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-blue-500/10 text-blue-400 rounded-lg font-medium border border-blue-500/20">
                                        <i data-lucide="gauge" class="w-3 h-3 mr-1"></i>
                                        Rate Limit
                                    </span>
                                <?php endif; ?>
                                <?php if ($site['threat_detection_enabled']): ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-purple-500/10 text-purple-400 rounded-lg font-medium border border-purple-500/20">
                                        <i data-lucide="radar" class="w-3 h-3 mr-1"></i>
                                        Threat Detection
                                    </span>
                                <?php endif; ?>
                            </div>

                        <div class="mb-4">
                            <form method="POST" class="inline-flex items-center gap-2 text-xs">
                                <input type="hidden" name="toggle_geo_allow_only" value="1">
                                <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                <?php $geoAllow = !empty($site['geo_allow_only']); ?>
                                <button type="submit" class="px-3 py-1.5 rounded-full border transition-all <?php echo $geoAllow ? 'bg-blue-500/10 text-blue-300 border-blue-500/30' : 'bg-zinc-900/60 text-zinc-300 border-white/10'; ?>">
                                    <span class="inline-flex w-2 h-2 rounded-full <?php echo $geoAllow ? 'bg-blue-400 animate-pulse' : 'bg-zinc-500'; ?>"></span>
                                    Somente países autorizados: <?php echo $geoAllow ? 'ATIVO' : 'DESLIGADO'; ?>
                                </button>
                            </form>
                        </div>

                        <!-- Geo-Blocking -->
                        <div class="mt-4 pt-4 border-t border-white/5 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-semibold text-white">Geo-Blocking</h4>
                                    <p class="text-xs text-zinc-500">Bloqueie ou permita tráfego por país (código ISO, ex: BR, US).</p>
                                </div>
                            </div>
                            <form method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                <input type="hidden" name="add_geo_rule" value="1">
                                <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                <input type="text" name="country_code" maxlength="2" pattern="[A-Za-z]{2}" placeholder="BR" class="sm:col-span-4 px-3 py-2 rounded-lg bg-zinc-900/60 border border-white/10 text-white text-sm uppercase focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <select name="geo_action" class="sm:col-span-4 px-3 py-2 rounded-lg bg-zinc-900/60 border border-white/10 text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="block">Bloquear</option>
                                    <option value="allow">Permitir</option>
                                </select>
                                <button type="submit" class="sm:col-span-4 px-4 py-2 bg-zinc-800 text-zinc-200 rounded-lg hover:bg-zinc-700 text-sm font-semibold transition-all w-full">Adicionar</button>
                            </form>
                            <div class="flex flex-wrap gap-2">
                                <?php $geoRules = $siteGeoRules[$site['id']] ?? []; ?>
                                <?php if (empty($geoRules)): ?>
                                    <span class="text-xs text-zinc-500">Nenhuma regra configurada.</span>
                                <?php else: ?>
                                    <?php foreach ($geoRules as $rule): ?>
                                        <form method="POST" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold border <?php echo $rule['action'] === 'block' ? 'bg-red-500/10 text-red-300 border-red-500/20' : 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20'; ?>">
                                            <span><?php echo htmlspecialchars($rule['country_code']); ?> · <?php echo $rule['action'] === 'block' ? 'Bloqueado' : 'Permitido'; ?></span>
                                            <input type="hidden" name="delete_geo_rule" value="1">
                                            <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                                            <button type="submit" class="text-zinc-400 hover:text-white" title="Remover">
                                                <i data-lucide="x" class="w-3 h-3"></i>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Firewall Rules -->
                        <div class="mt-4 pt-4 border-t border-white/5 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-semibold text-white">Regras Personalizadas (Firewall)</h4>
                                    <p class="text-xs text-zinc-500">Crie regras por caminho, IP, país ou User-Agent.</p>
                                </div>
                            </div>
                            <form method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-2 items-center">
                                <input type="hidden" name="add_fw_rule" value="1">
                                <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                <div class="lg:col-span-3">
                                    <select name="match_type" class="w-full px-3 py-2 rounded-lg bg-zinc-900/60 border border-white/10 text-white text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="path_prefix">Path começa com</option>
                                        <option value="ip">IP igual</option>
                                        <option value="country">País (BR, US)</option>
                                        <option value="user_agent">User-Agent contém</option>
                                    </select>
                                </div>
                                <div class="lg:col-span-4">
                                    <input type="text" name="match_value" placeholder="/admin ou 1.2.3.4" class="w-full px-3 py-2 rounded-lg bg-zinc-900/60 border border-white/10 text-white text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                </div>
                                <div class="lg:col-span-3 grid grid-cols-2 gap-2 w-full">
                                    <select name="fw_action" class="col-span-1 px-3 py-2 rounded-lg bg-zinc-900/60 border border-white/10 text-white text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="block">Bloquear</option>
                                        <option value="allow">Permitir</option>
                                        <option value="log">Somente logar</option>
                                    </select>
                                    <input type="number" name="priority" value="0" class="col-span-1 px-2 py-2 rounded-lg bg-zinc-900/60 border border-white/10 text-white text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500" title="Prioridade (maior primeiro)">
                                </div>
                                <div class="lg:col-span-2">
                                    <button type="submit" class="w-full px-4 py-2 bg-zinc-800 text-zinc-200 rounded-lg hover:bg-zinc-700 text-xs font-semibold transition-all">Adicionar Regra</button>
                                </div>
                            </form>
                            <div class="space-y-1 max-h-32 overflow-y-auto">
                                <?php $fwRules = $siteFirewallRules[$site['id']] ?? []; ?>
                                <?php if (empty($fwRules)): ?>
                                    <p class="text-xs text-zinc-500">Nenhuma regra configurada.</p>
                                <?php else: ?>
                                    <?php foreach ($fwRules as $rule): ?>
                                        <form method="POST" class="flex items-center justify-between px-3 py-1.5 rounded-lg bg-zinc-900/60 border border-white/5 text-xs gap-2">
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-zinc-300 font-medium">
                                                    <?php echo htmlspecialchars($rule['match_type'] . ' → ' . $rule['match_value']); ?>
                                                </span>
                                                <span class="text-[10px] text-zinc-500">
                                                    Ação: <?php echo strtoupper($rule['action']); ?> · Prioridade: <?php echo (int)$rule['priority']; ?>
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <input type="hidden" name="delete_fw_rule" value="1">
                                                <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                                                <button type="submit" class="text-zinc-500 hover:text-red-400" title="Remover">
                                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                                </button>
                                            </div>
                                        </form>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                            <!-- Actions -->
                            <div class="flex gap-2 pt-4 border-t border-white/5">
                                <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente <?php echo $site['is_active'] ? 'desativar' : 'ativar'; ?> este site?');">
                                    <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                    <input type="hidden" name="update_site" value="1">
                                    <input type="hidden" name="display_name" value="<?php echo htmlspecialchars($site['display_name'] ?? ''); ?>">
                                    <input type="hidden" name="cloudflare_zone_id" value="<?php echo htmlspecialchars($site['cloudflare_zone_id'] ?? ''); ?>">
                                    <input type="hidden" name="security_level" value="<?php echo htmlspecialchars($site['security_level']); ?>">
                                    <input type="hidden" name="auto_block" value="<?php echo $site['auto_block'] ? '1' : '0'; ?>">
                                    <input type="hidden" name="rate_limit_enabled" value="<?php echo $site['rate_limit_enabled'] ? '1' : '0'; ?>">
                                    <input type="hidden" name="threat_detection_enabled" value="<?php echo $site['threat_detection_enabled'] ? '1' : '0'; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $site['is_active'] ? '0' : '1'; ?>">
                                    <button type="submit" class="flex-1 px-3 py-2 <?php echo $site['is_active'] ? 'bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 border border-emerald-500/20'; ?> rounded-xl font-semibold text-sm transition-all">
                                        <i data-lucide="<?php echo $site['is_active'] ? 'pause' : 'play'; ?>" class="w-4 h-4 inline mr-1"></i>
                                        <?php echo $site['is_active'] ? 'Desativar' : 'Ativar'; ?>
                                    </button>
                                </form>
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($site)); ?>)" class="flex-1 px-4 py-2 bg-zinc-800 text-zinc-300 rounded-xl hover:bg-zinc-700 font-semibold text-sm transition-all">
                                    <i data-lucide="edit" class="w-4 h-4 inline mr-1"></i>
                                    Editar
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente remover este site? Esta ação não pode ser desfeita.');">
                                    <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                    <input type="hidden" name="delete_site" value="1">
                                    <button type="submit" class="px-4 py-2 bg-red-500/10 text-red-400 rounded-xl hover:bg-red-500/20 font-semibold text-sm transition-all border border-red-500/20">
                                        <i data-lucide="trash-2" class="w-4 h-4 inline"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Verify Modal -->
    <div id="verifyModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="glass-card rounded-2xl p-4 sm:p-6 max-w-2xl w-full mx-auto my-4 border border-white/10">
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <h3 class="text-lg sm:text-xl font-bold text-white">Verificar Domínio</h3>
                <button onclick="closeVerifyModal()" class="text-zinc-400 hover:text-white transition-colors flex-shrink-0">
                    <i data-lucide="x" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                </button>
            </div>
            
            <div class="mb-4 sm:mb-6">
                <p class="text-xs sm:text-sm text-zinc-400 mb-4">Para provar que você possui <span id="verify_domain_name" class="text-white font-bold break-all"></span>, por favor adicione um dos seguintes registros:</p>
                
                <div class="space-y-3 sm:space-y-4">
                    <!-- Método 1: DNS TXT -->
                    <div class="bg-zinc-900/50 rounded-lg p-3 sm:p-4 border border-white/5">
                        <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-semibold text-white">Método 1: Registro DNS TXT</h4>
                            <button onclick="copyVerificationToken('dns')" class="text-zinc-400 hover:text-blue-400 transition-colors flex items-center gap-1 text-xs flex-shrink-0" title="Copiar">
                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                <span class="hidden sm:inline">Copiar</span>
                            </button>
                        </div>
                        <div class="bg-black p-2.5 sm:p-3 rounded border border-white/10 overflow-x-auto">
                            <code class="text-[9px] sm:text-xs font-mono block break-all leading-relaxed">
                                <span class="text-zinc-400 inline">safenode-verification=</span>
                                <span id="verify_token_dns" class="text-blue-400 break-all inline word-break-all"></span>
                            </code>
                        </div>
                        <div class="mt-2 p-2.5 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                            <p class="text-xs text-blue-400 font-semibold mb-1.5 flex items-center gap-1.5">
                                <i data-lucide="info" class="w-3.5 h-3.5"></i>
                                Onde adicionar? (Cloudflare)
                            </p>
                            <div class="text-xs text-zinc-400 space-y-2">
                                <div class="space-y-1.5">
                                    <p class="text-zinc-300"><strong class="text-white">Passo a passo na Cloudflare:</strong></p>
                                    <ol class="list-decimal ml-4 space-y-1.5">
                                    <li>Na página <strong class="text-white">DNS → Registros</strong> (onde você está agora)</li>
                                    <li>Clique no botão azul <strong class="text-white">"+ Adicionar registro"</strong></li>
                                    <li>No campo <strong class="text-white">Tipo</strong>, selecione <strong class="text-blue-400">TXT</strong> (não deixe como "A")</li>
                                    <li>No campo <strong class="text-white">Nome</strong>, deixe em branco ou coloque <code class="text-blue-400 bg-black px-1 py-0.5 rounded">@</code></li>
                                    <li>No campo <strong class="text-white">Conteúdo</strong> ou <strong class="text-white">Conteúdo TXT</strong>, cole o valor completo acima (incluindo "safenode-verification=")</li>
                                    <li>Deixe <strong class="text-white">Proxy</strong> desligado (não precisa estar ativo para registro TXT)</li>
                                    <li>Clique em <strong class="text-white">Salvar</strong></li>
                                </ol>
                                </div>
                                <div class="pt-2 border-t border-white/5 mt-2">
                                    <p class="text-zinc-500 text-[10px] leading-relaxed">
                                        <strong class="text-zinc-400">💡 Dica:</strong> Após adicionar, pode levar alguns minutos para o DNS propagar. Você pode verificar se o registro foi adicionado corretamente na lista de registros DNS.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Método 2: Arquivo HTML -->
                    <div class="bg-zinc-900/50 rounded-lg p-3 sm:p-4 border border-white/5">
                        <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-semibold text-white">Método 2: Arquivo HTML</h4>
                            <button onclick="copyVerificationToken('file')" class="text-zinc-400 hover:text-blue-400 transition-colors flex items-center gap-1 text-xs flex-shrink-0" title="Copiar">
                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                <span class="hidden sm:inline">Copiar</span>
                            </button>
                        </div>
                        <p class="text-xs text-zinc-400 mb-2">Crie um arquivo chamado <code class="text-blue-400 break-all inline">safenode-verification.txt</code> na raiz do seu site com o seguinte conteúdo:</p>
                        <div class="bg-black p-2.5 sm:p-3 rounded border border-white/10 overflow-x-auto">
                            <code id="verify_token_file" class="text-[9px] sm:text-xs font-mono text-blue-400 break-all block word-break-all whitespace-normal leading-relaxed"></code>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="verify_site" value="1">
                <input type="hidden" name="site_id" id="verify_site_id">
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <button type="button" onclick="closeVerifyModal()" class="w-full sm:flex-1 px-4 py-2.5 bg-zinc-800 text-zinc-300 rounded-xl hover:bg-zinc-700 font-semibold transition-all text-sm">
                        Cancelar
                    </button>
                    <button type="submit" class="w-full sm:flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all text-sm">
                        Verificar Agora
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="siteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="glass-card rounded-2xl p-6 max-w-lg w-full mx-4 border border-white/10">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white">Editar Site</h3>
                <button onclick="closeModal()" class="text-zinc-400 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form method="POST" id="siteForm">
                <input type="hidden" name="site_id" id="site_id">
                <input type="hidden" name="update_site" value="1">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Domínio</label>
                        <input type="text" id="domain" disabled class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-zinc-400">
                        <p class="mt-1.5 text-xs text-zinc-500">O domínio não pode ser alterado após criação</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Nome de Exibição</label>
                        <input type="text" name="display_name" id="display_name" placeholder="Meu Site" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Cloudflare Zone ID</label>
                        <input type="text" name="cloudflare_zone_id" id="cloudflare_zone_id" placeholder="Opcional" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Nível de Segurança</label>
                        <select name="security_level" id="security_level" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="low">Baixo - Proteção básica</option>
                            <option value="medium">Médio - Proteção recomendada</option>
                            <option value="high">Alto - Máxima proteção</option>
                            <option value="under_attack">Sob Ataque - Modo de emergência</option>
                        </select>
                    </div>
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="auto_block" id="auto_block" class="w-4 h-4 text-blue-600 bg-zinc-800 border-white/10 rounded focus:ring-blue-500">
                            <label for="auto_block" class="text-sm font-medium text-zinc-300">Bloqueio Automático</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="rate_limit_enabled" id="rate_limit_enabled" class="w-4 h-4 text-blue-600 bg-zinc-800 border-white/10 rounded focus:ring-blue-500">
                            <label for="rate_limit_enabled" class="text-sm font-medium text-zinc-300">Rate Limiting</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="threat_detection_enabled" id="threat_detection_enabled" class="w-4 h-4 text-blue-600 bg-zinc-800 border-white/10 rounded focus:ring-blue-500">
                            <label for="threat_detection_enabled" class="text-sm font-medium text-zinc-300">Detecção de Ameaças</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="is_active" id="is_active" class="w-4 h-4 text-blue-600 bg-zinc-800 border-white/10 rounded focus:ring-blue-500">
                            <label for="is_active" class="text-sm font-medium text-zinc-300">Site Ativo</label>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2.5 bg-zinc-800 text-zinc-300 rounded-xl hover:bg-zinc-700 font-semibold transition-all">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        function openEditModal(site) {
            document.getElementById('site_id').value = site.id;
            document.getElementById('domain').value = site.domain;
            document.getElementById('display_name').value = site.display_name || '';
            document.getElementById('cloudflare_zone_id').value = site.cloudflare_zone_id || '';
            document.getElementById('security_level').value = site.security_level || 'medium';
            document.getElementById('auto_block').checked = site.auto_block == 1 || site.auto_block === true;
            document.getElementById('rate_limit_enabled').checked = site.rate_limit_enabled == 1 || site.rate_limit_enabled === true;
            document.getElementById('threat_detection_enabled').checked = site.threat_detection_enabled == 1 || site.threat_detection_enabled === true;
            document.getElementById('is_active').checked = site.is_active == 1 || site.is_active === true;
            document.getElementById('siteModal').classList.remove('hidden');
            document.getElementById('siteModal').classList.add('flex');
            lucide.createIcons();
        }

        function closeModal() {
            document.getElementById('siteModal').classList.add('hidden');
            document.getElementById('siteModal').classList.remove('flex');
        }

        function openVerifyModal(site) {
            try {
                // Verificar se o site tem os dados necessários
                if (!site || !site.id) {
                    console.error('Site data is missing');
                    alert('Erro: Dados do site não encontrados.');
                    return;
                }
                
                // Preencher os campos do modal
                const modal = document.getElementById('verifyModal');
                if (!modal) {
                    console.error('Modal element not found');
                    alert('Erro: Modal não encontrado.');
                    return;
                }
                
                // Preencher dados
                document.getElementById('verify_site_id').value = site.id;
                document.getElementById('verify_domain_name').textContent = site.domain || '';
                const cloudflareDomain = document.getElementById('verify_domain_cloudflare');
                if (cloudflareDomain) {
                    cloudflareDomain.textContent = site.domain || '';
                }
                document.getElementById('verify_token_dns').textContent = site.verification_token || '';
                document.getElementById('verify_token_file').textContent = site.verification_token || '';
                
                // Mostrar modal com flex para centralizar
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                
                // Prevenir scroll do body
                document.body.style.overflow = 'hidden';
                
                // Atualizar ícones do Lucide
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            } catch (error) {
                console.error('Error opening verify modal:', error);
                alert('Erro ao abrir o modal de verificação: ' + error.message);
            }
        }
        
        function copyVerificationToken(type) {
            const tokenElement = document.getElementById(type === 'dns' ? 'verify_token_dns' : 'verify_token_file');
            const token = tokenElement.textContent;
            const fullText = type === 'dns' 
                ? 'safenode-verification=' + token 
                : token;
            
            navigator.clipboard.writeText(fullText).then(function() {
                // Feedback visual
                const button = event.target.closest('button');
                if (button) {
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i><span class="hidden sm:inline">Copiado!</span>';
                    button.classList.add('text-green-400');
                    
                    // Atualizar ícone
                    lucide.createIcons();
                    
                    setTimeout(function() {
                        button.innerHTML = originalHTML;
                        button.classList.remove('text-green-400');
                        lucide.createIcons();
                    }, 2000);
                }
            }).catch(function(err) {
                console.error('Erro ao copiar:', err);
                // Fallback: selecionar texto manualmente
                const range = document.createRange();
                range.selectNode(tokenElement);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
            });
        }

        function closeVerifyModal() {
            try {
                const modal = document.getElementById('verifyModal');
                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.add('hidden');
                    modal.classList.remove('flex', 'items-center', 'justify-center');
                    document.body.style.overflow = '';
                }
            } catch (error) {
                console.error('Error closing verify modal:', error);
            }
        }
        
        // Validação de domínio no formulário
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            
            const domainInput = document.querySelector('input[name="domain"]');
            if (domainInput) {
                domainInput.addEventListener('blur', function() {
                    let domain = this.value.trim();
                    // Remove http://, https://, www.
                    domain = domain.replace(/^https?:\/\//, '').replace(/^www\./, '').replace(/\/$/, '');
                    this.value = domain;
                });
            }
            
            // Auto-fechar mensagens após 5 segundos
            const messageDiv = document.querySelector('.bg-emerald-500\\/10, .bg-red-500\\/10');
            if (messageDiv) {
                setTimeout(() => {
                    messageDiv.style.transition = 'opacity 0.5s';
                    messageDiv.style.opacity = '0';
                    setTimeout(() => messageDiv.remove(), 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>
