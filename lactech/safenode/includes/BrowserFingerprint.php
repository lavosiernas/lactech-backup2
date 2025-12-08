<?php
/**
 * SafeNode - Browser Fingerprint
 * Sistema de coleta e análise de fingerprint do navegador
 * 
 * Detecta bots avançados que falsificam User-Agent
 */

class BrowserFingerprint {
    private $db;
    private $cache;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Coleta fingerprint do navegador via JavaScript
     * Retorna código JavaScript para incluir na página
     */
    public static function getCollectionScript() {
        return "
        <script>
        (function() {
            function collectFingerprint() {
                const fp = {
                    // User Agent
                    ua: navigator.userAgent,
                    
                    // Screen
                    screen: {
                        width: screen.width,
                        height: screen.height,
                        colorDepth: screen.colorDepth,
                        pixelDepth: screen.pixelDepth
                    },
                    
                    // Timezone
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    timezoneOffset: new Date().getTimezoneOffset(),
                    
                    // Language
                    language: navigator.language,
                    languages: navigator.languages ? navigator.languages.join(',') : navigator.language,
                    
                    // Platform
                    platform: navigator.platform,
                    
                    // Hardware
                    hardwareConcurrency: navigator.hardwareConcurrency || 0,
                    deviceMemory: navigator.deviceMemory || 0,
                    
                    // Canvas fingerprint
                    canvas: null,
                    
                    // WebGL fingerprint
                    webgl: null,
                    
                    // Fonts (simplificado)
                    fonts: null,
                    
                    // Plugins
                    plugins: Array.from(navigator.plugins || []).map(p => p.name).join(','),
                    
                    // Mime types
                    mimeTypes: Array.from(navigator.mimeTypes || []).map(m => m.type).join(','),
                    
                    // Touch support
                    touchSupport: 'ontouchstart' in window || navigator.maxTouchPoints > 0,
                    
                    // Cookie enabled
                    cookiesEnabled: navigator.cookieEnabled,
                    
                    // Do Not Track
                    doNotTrack: navigator.doNotTrack || 'unknown',
                    
                    // Timestamp
                    timestamp: Date.now()
                };
                
                // Canvas fingerprint
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = 200;
                    canvas.height = 50;
                    const ctx = canvas.getContext('2d');
                    ctx.textBaseline = 'top';
                    ctx.font = '14px Arial';
                    ctx.fillStyle = '#f60';
                    ctx.fillRect(125, 1, 62, 20);
                    ctx.fillStyle = '#069';
                    ctx.fillText('SafeNode', 2, 15);
                    ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
                    ctx.fillText('SafeNode', 4, 17);
                    fp.canvas = canvas.toDataURL();
                } catch(e) {
                    fp.canvas = 'error';
                }
                
                // WebGL fingerprint
                try {
                    const canvas = document.createElement('canvas');
                    const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
                    if (gl) {
                        const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                        fp.webgl = {
                            vendor: gl.getParameter(gl.VENDOR),
                            renderer: gl.getParameter(gl.RENDERER),
                            version: gl.getParameter(gl.VERSION),
                            shadingLanguageVersion: gl.getParameter(gl.SHADING_LANGUAGE_VERSION),
                            debugVendor: debugInfo ? gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) : null,
                            debugRenderer: debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : null
                        };
                    }
                } catch(e) {
                    fp.webgl = 'error';
                }
                
                // Fonts detection (simplificado - verifica algumas fontes comuns)
                try {
                    const testFonts = ['Arial', 'Verdana', 'Times New Roman', 'Courier New', 'Georgia', 'Palatino', 'Garamond', 'Bookman', 'Comic Sans MS', 'Trebuchet MS', 'Arial Black', 'Impact'];
                    const baseFonts = 'monospace, sans-serif, serif';
                    const testString = 'mmmmmmmmmmlli';
                    const testSize = '72px';
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    const baseWidths = {};
                    const baseFontsArray = baseFonts.split(', ');
                    baseFontsArray.forEach(baseFont => {
                        ctx.font = testSize + ' ' + baseFont;
                        baseWidths[baseFont] = ctx.measureText(testString).width;
                    });
                    const detectedFonts = [];
                    testFonts.forEach(font => {
                        let detected = false;
                        baseFontsArray.forEach(baseFont => {
                            ctx.font = testSize + ' ' + font + ', ' + baseFont;
                            const width = ctx.measureText(testString).width;
                            if (width !== baseWidths[baseFont]) {
                                detected = true;
                            }
                        });
                        if (detected) {
                            detectedFonts.push(font);
                        }
                    });
                    fp.fonts = detectedFonts.join(',');
                } catch(e) {
                    fp.fonts = 'error';
                }
                
                return fp;
            }
            
            // Coletar e enviar fingerprint
            const fingerprint = collectFingerprint();
            
            // Enviar para servidor via fetch
            fetch('/safenode/api/collect-fingerprint.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    fingerprint: fingerprint,
                    page_url: window.location.href,
                    referer: document.referrer
                })
            }).catch(() => {
                // Silently fail - não bloquear página se falhar
            });
        })();
        </script>
        ";
    }
    
    /**
     * Analisa fingerprint e detecta bots
     * 
     * @param array $fingerprint Dados do fingerprint
     * @return array Análise do fingerprint
     */
    public function analyzeFingerprint($fingerprint) {
        $suspicionScore = 0;
        $reasons = [];
        
        // 1. Verificar User-Agent suspeito
        $ua = $fingerprint['ua'] ?? '';
        if (empty($ua) || strlen($ua) < 10) {
            $suspicionScore += 30;
            $reasons[] = 'User-Agent ausente ou muito curto';
        }
        
        // Verificar bots conhecidos no User-Agent
        $botPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'headless', 'phantom', 'selenium',
            'webdriver', 'puppeteer', 'playwright', 'curl', 'wget', 'python-requests'
        ];
        foreach ($botPatterns as $pattern) {
            if (stripos($ua, $pattern) !== false) {
                $suspicionScore += 50;
                $reasons[] = "User-Agent contém padrão de bot: $pattern";
                break;
            }
        }
        
        // 2. Verificar Canvas fingerprint
        if (empty($fingerprint['canvas']) || $fingerprint['canvas'] === 'error') {
            $suspicionScore += 20;
            $reasons[] = 'Canvas fingerprint ausente ou inválido';
        }
        
        // 3. Verificar WebGL
        if (empty($fingerprint['webgl']) || $fingerprint['webgl'] === 'error') {
            $suspicionScore += 15;
            $reasons[] = 'WebGL fingerprint ausente ou inválido';
        } else {
            // Verificar se WebGL renderer é suspeito
            $webgl = is_array($fingerprint['webgl']) ? $fingerprint['webgl'] : [];
            $renderer = $webgl['renderer'] ?? '';
            if (stripos($renderer, 'swiftshader') !== false || stripos($renderer, 'llvmpipe') !== false) {
                $suspicionScore += 25;
                $reasons[] = 'WebGL renderer indica virtualização (bot)';
            }
        }
        
        // 4. Verificar Fonts
        if (empty($fingerprint['fonts']) || $fingerprint['fonts'] === 'error') {
            $suspicionScore += 10;
            $reasons[] = 'Fonts detection falhou';
        } elseif (strlen($fingerprint['fonts']) < 10) {
            // Poucas fontes detectadas pode indicar bot
            $suspicionScore += 15;
            $reasons[] = 'Poucas fontes detectadas';
        }
        
        // 5. Verificar Hardware (valores suspeitos)
        $hardwareConcurrency = (int)($fingerprint['hardwareConcurrency'] ?? 0);
        if ($hardwareConcurrency === 0 || $hardwareConcurrency > 32) {
            $suspicionScore += 10;
            $reasons[] = 'Hardware concurrency suspeito';
        }
        
        $deviceMemory = (float)($fingerprint['deviceMemory'] ?? 0);
        if ($deviceMemory === 0 || $deviceMemory > 64) {
            $suspicionScore += 10;
            $reasons[] = 'Device memory suspeito';
        }
        
        // 6. Verificar Screen (valores suspeitos)
        $screen = $fingerprint['screen'] ?? [];
        $screenWidth = (int)($screen['width'] ?? 0);
        $screenHeight = (int)($screen['height'] ?? 0);
        
        if ($screenWidth === 0 || $screenHeight === 0) {
            $suspicionScore += 20;
            $reasons[] = 'Dimensões de tela inválidas';
        } elseif ($screenWidth < 320 || $screenHeight < 240) {
            $suspicionScore += 15;
            $reasons[] = 'Dimensões de tela muito pequenas';
        }
        
        // 7. Verificar Plugins (bots geralmente têm poucos ou nenhum plugin)
        $plugins = $fingerprint['plugins'] ?? '';
        if (empty($plugins)) {
            $suspicionScore += 10;
            $reasons[] = 'Nenhum plugin detectado';
        }
        
        // 8. Verificar se fingerprint é muito comum (indicador de bot)
        $fingerprintHash = $this->hashFingerprint($fingerprint);
        $commonFingerprints = $this->getCommonFingerprints();
        if (in_array($fingerprintHash, $commonFingerprints)) {
            $suspicionScore += 30;
            $reasons[] = 'Fingerprint muito comum (possível bot)';
        }
        
        // Normalizar score (0-100)
        $suspicionScore = min(100, $suspicionScore);
        
        return [
            'suspicion_score' => $suspicionScore,
            'is_bot' => $suspicionScore >= 50,
            'reasons' => $reasons,
            'fingerprint_hash' => $fingerprintHash
        ];
    }
    
    /**
     * Gera hash do fingerprint para comparação
     */
    private function hashFingerprint($fingerprint) {
        // Criar string única do fingerprint (sem timestamp)
        $fpString = json_encode([
            'ua' => $fingerprint['ua'] ?? '',
            'screen' => $fingerprint['screen'] ?? [],
            'timezone' => $fingerprint['timezone'] ?? '',
            'language' => $fingerprint['language'] ?? '',
            'platform' => $fingerprint['platform'] ?? '',
            'hardwareConcurrency' => $fingerprint['hardwareConcurrency'] ?? 0,
            'canvas' => substr($fingerprint['canvas'] ?? '', 0, 100), // Primeiros 100 chars
            'webgl_vendor' => is_array($fingerprint['webgl'] ?? []) ? ($fingerprint['webgl']['vendor'] ?? '') : '',
            'webgl_renderer' => is_array($fingerprint['webgl'] ?? []) ? ($fingerprint['webgl']['renderer'] ?? '') : '',
            'fonts' => $fingerprint['fonts'] ?? ''
        ]);
        
        return hash('sha256', $fpString);
    }
    
    /**
     * Obtém lista de fingerprints comuns (bots conhecidos)
     */
    private function getCommonFingerprints() {
        // Em produção, isso viria do banco de dados
        // Por enquanto, retornar array vazio (será populado com uso)
        $cacheKey = 'common_fingerprints';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Buscar do banco se disponível
        if ($this->db) {
            try {
                $stmt = $this->db->query("
                    SELECT fingerprint_hash, COUNT(*) as count
                    FROM safenode_fingerprints
                    WHERE is_bot = 1
                    GROUP BY fingerprint_hash
                    HAVING count > 10
                    ORDER BY count DESC
                    LIMIT 100
                ");
                $common = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $this->cache->set($cacheKey, $common, 3600); // Cache por 1 hora
                return $common;
            } catch (PDOException $e) {
                // Tabela pode não existir ainda
            }
        }
        
        return [];
    }
    
    /**
     * Salva fingerprint no banco de dados
     */
    public function saveFingerprint($ipAddress, $fingerprint, $analysis, $siteId = null) {
        if (!$this->db) return false;
        
        try {
            // Garantir que tabela existe
            $this->ensureTableExists();
            
            $stmt = $this->db->prepare("
                INSERT INTO safenode_fingerprints
                (ip_address, fingerprint_hash, fingerprint_data, suspicion_score, is_bot, site_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    suspicion_score = VALUES(suspicion_score),
                    is_bot = VALUES(is_bot),
                    last_seen = NOW()
            ");
            
            $stmt->execute([
                $ipAddress,
                $analysis['fingerprint_hash'],
                json_encode($fingerprint),
                $analysis['suspicion_score'],
                $analysis['is_bot'] ? 1 : 0,
                $siteId
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("SafeNode BrowserFingerprint Save Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Garante que tabela de fingerprints existe
     */
    private function ensureTableExists() {
        try {
            $this->db->query("SELECT 1 FROM safenode_fingerprints LIMIT 1");
        } catch (PDOException $e) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_fingerprints (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    fingerprint_hash VARCHAR(64) NOT NULL,
                    fingerprint_data TEXT,
                    suspicion_score INT DEFAULT 0,
                    is_bot TINYINT(1) DEFAULT 0,
                    site_id INT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_ip (ip_address),
                    INDEX idx_hash (fingerprint_hash),
                    INDEX idx_bot (is_bot, suspicion_score),
                    UNIQUE KEY unique_ip_hash (ip_address, fingerprint_hash)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }
}



