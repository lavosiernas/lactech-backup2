<?php
/**
 * Classe de Integração com Lactech
 * Conecta AgroNews360 com o sistema Lactech para criar um ecossistema integrado
 */

require_once __DIR__ . '/Database.class.php';

class LactechIntegration {
    private $db;
    private $lactechDb;
    private $lactechConfig;
    
    public function __construct() {
        $this->db = Database::getInstance();
        // Não carregar config do Lactech automaticamente - apenas quando necessário
        // Isso evita conflitos com as constantes do AgroNews
        // $this->loadLactechConfig();
    }
    
    /**
     * Carregar configuração do Lactech
     * IMPORTANTE: Não sobrescreve as constantes do AgroNews
     * Usa arquivo de configuração específico para acesso ao banco do Lactech
     */
    private function loadLactechConfig() {
        // Tentar carregar configuração específica do AgroNews para acessar o Lactech
        $lactechConfigPath = __DIR__ . '/config_lactech_db.php';
        
        if (file_exists($lactechConfigPath)) {
            // Carregar config específica do AgroNews para Lactech
            require_once $lactechConfigPath;
            
            // Usar constantes específicas do Lactech (não conflitam com AgroNews)
            $this->lactechConfig = [
                'host' => defined('LACTECH_DB_HOST') ? LACTECH_DB_HOST : 'localhost',
                'name' => defined('LACTECH_DB_NAME') ? LACTECH_DB_NAME : 'lactech_lgmato',
                'user' => defined('LACTECH_DB_USER') ? LACTECH_DB_USER : 'root',
                'pass' => defined('LACTECH_DB_PASS') ? LACTECH_DB_PASS : '',
                'charset' => defined('LACTECH_DB_CHARSET') ? LACTECH_DB_CHARSET : 'utf8mb4'
            ];
            
            // Conectar ao banco do Lactech
            $this->connectToLactech();
        } else {
            // Fallback: usar valores padrão baseados no ambiente
            $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
                       strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
            
            $this->lactechConfig = [
                'host' => 'localhost',
                'name' => $isLocal ? 'lactech_lgmato' : 'u311882628_lactech_lgmato',
                'user' => $isLocal ? 'root' : 'u311882628_xandriaAgro',
                'pass' => $isLocal ? '' : 'Lavosier0012!',
                'charset' => 'utf8mb4'
            ];
            
            $this->connectToLactech();
        }
    }
    
    /**
     * Conectar ao banco do Lactech
     */
    private function connectToLactech() {
        try {
            // Se não tiver config, tentar carregar
            if (!$this->lactechConfig) {
                $this->loadLactechConfig();
            }
            
            if (!$this->lactechConfig) {
                return false;
            }
            
            $dsn = "mysql:host={$this->lactechConfig['host']};dbname={$this->lactechConfig['name']};charset={$this->lactechConfig['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->lactechDb = new PDO($dsn, $this->lactechConfig['user'], $this->lactechConfig['pass'], $options);
        } catch (Exception $e) {
            error_log("Erro ao conectar ao Lactech: " . $e->getMessage());
            $this->lactechDb = null;
        }
    }
    
    /**
     * Verificar se a conexão com Lactech está ativa
     */
    public function isLactechConnected() {
        // Tentar conectar se não estiver conectado
        if ($this->lactechDb === null) {
            $this->loadLactechConfig();
        }
        
        // Verificar se a conexão está ativa
        if ($this->lactechDb !== null) {
            try {
                $this->lactechDb->query('SELECT 1');
                return true;
            } catch (PDOException $e) {
                // Reconectar se necessário
                $this->connectToLactech();
                return $this->lactechDb !== null;
            }
        }
        
        return false;
    }
    
    /**
     * Obter conexão PDO do Lactech
     */
    public function getLactechConnection() {
        if (!$this->isLactechConnected()) {
            return null;
        }
        
        // Verificar se a conexão ainda está ativa
        try {
            $this->lactechDb->query('SELECT 1');
        } catch (PDOException $e) {
            // Reconectar se necessário
            $this->connectToLactech();
        }
        
        return $this->lactechDb;
    }
    
    /**
     * Sincronizar usuários do Lactech
     */
    public function syncUsers() {
        if (!$this->isLactechConnected()) {
            return ['success' => false, 'error' => 'Lactech não está conectado'];
        }
        
        try {
            // Buscar usuários do Lactech
            $stmt = $this->lactechDb->prepare("
                SELECT id, name, email, role, is_active 
                FROM users 
                WHERE is_active = 1
            ");
            $stmt->execute();
            $lactechUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $synced = 0;
            $pdo = $this->db->getConnection();
            
            foreach ($lactechUsers as $user) {
                // Verificar se já existe no AgroNews
                $existing = $this->db->query(
                    "SELECT id FROM users WHERE lactech_user_id = ?",
                    [$user['id']]
                );
                
                if (empty($existing)) {
                    // Criar usuário no AgroNews
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, role, is_active, lactech_user_id) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    // Gerar senha temporária (usuário deve redefinir)
                    $tempPassword = password_hash('temp_' . time(), PASSWORD_DEFAULT);
                    
                    // Todos os usuários são tratados igualmente (sem distinção de admin)
                    $defaultRole = 'viewer';
                    $stmt->execute([
                        $user['name'],
                        $user['email'],
                        $tempPassword,
                        $defaultRole,
                        $user['is_active'],
                        $user['id']
                    ]);
                    
                    $synced++;
                } else {
                    // Atualizar dados do usuário
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, role = ?, is_active = ?
                        WHERE lactech_user_id = ?
                    ");
                    
                    // Todos os usuários são tratados igualmente (sem distinção de admin)
                    $defaultRole = 'viewer';
                    $stmt->execute([
                        $user['name'],
                        $user['email'],
                        $defaultRole,
                        $user['is_active'],
                        $user['id']
                    ]);
                }
                
                // Registrar sincronização
                $this->logSync('user', $user['id'], $existing[0]['id'] ?? $pdo->lastInsertId());
            }
            
            return ['success' => true, 'synced' => $synced, 'total' => count($lactechUsers)];
            
        } catch (Exception $e) {
            error_log("Erro ao sincronizar usuários: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Sincronizar estatísticas da fazenda
     */
    public function syncFarmStats($farmId = 1) {
        if (!$this->isLactechConnected()) {
            return ['success' => false, 'error' => 'Lactech não está conectado'];
        }
        
        try {
            // Buscar estatísticas do Lactech
            $stats = [];
            
            // Total de animais
            $stmt = $this->lactechDb->prepare("SELECT COUNT(*) as total FROM animals WHERE is_active = 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_animals'] = $result['total'] ?? 0;
            
            // Animais ativos
            $stmt = $this->lactechDb->prepare("SELECT COUNT(*) as total FROM animals WHERE is_active = 1 AND status = 'active'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['active_animals'] = $result['total'] ?? 0;
            
            // Produção do dia
            $stmt = $this->lactechDb->prepare("
                SELECT SUM(volume) as total 
                FROM milk_production 
                WHERE DATE(production_date) = CURDATE()
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['daily_production'] = $result['total'] ?? 0;
            
            // Produção total (últimos 30 dias)
            $stmt = $this->lactechDb->prepare("
                SELECT SUM(volume) as total 
                FROM milk_production 
                WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_production'] = $result['total'] ?? 0;
            
            // Animais prenhes
            $stmt = $this->lactechDb->prepare("
                SELECT COUNT(*) as total 
                FROM animals 
                WHERE is_active = 1 AND pregnancy_status = 'pregnant'
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['pregnant_animals'] = $result['total'] ?? 0;
            
            // Salvar estatísticas no AgroNews
            $pdo = $this->db->getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO agronews_farm_stats 
                (farm_id, stat_date, total_animals, total_production, daily_production, active_animals, pregnant_animals, stats_data) 
                VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                total_animals = VALUES(total_animals),
                total_production = VALUES(total_production),
                daily_production = VALUES(daily_production),
                active_animals = VALUES(active_animals),
                pregnant_animals = VALUES(pregnant_animals),
                stats_data = VALUES(stats_data),
                last_updated = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                $farmId,
                $stats['total_animals'],
                $stats['total_production'],
                $stats['daily_production'],
                $stats['active_animals'],
                $stats['pregnant_animals'],
                json_encode($stats)
            ]);
            
            return ['success' => true, 'stats' => $stats];
            
        } catch (Exception $e) {
            error_log("Erro ao sincronizar estatísticas: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Criar notícia relacionada à fazenda
     */
    public function createFarmNews($title, $content, $categoryId = null, $relatedType = 'other', $relatedId = null) {
        try {
            $pdo = $this->db->getConnection();
            
            // Criar slug
            $slug = $this->createSlug($title);
            
            // Inserir artigo
            $stmt = $pdo->prepare("
                INSERT INTO agronews_articles 
                (title, slug, summary, content, category_id, source, is_published, published_at) 
                VALUES (?, ?, ?, ?, ?, 'Fazenda', 1, NOW())
            ");
            
            $summary = mb_substr(strip_tags($content), 0, 250);
            $stmt->execute([$title, $slug, $summary, $content, $categoryId]);
            
            $articleId = $pdo->lastInsertId();
            
            // Vincular à fazenda
            $stmt = $pdo->prepare("
                INSERT INTO agronews_farm_news 
                (article_id, farm_id, related_type, animal_id, production_id) 
                VALUES (?, 1, ?, ?, ?)
            ");
            
            $animalId = ($relatedType === 'animal') ? $relatedId : null;
            $productionId = ($relatedType === 'production') ? $relatedId : null;
            
            $stmt->execute([$articleId, $relatedType, $animalId, $productionId]);
            
            return ['success' => true, 'article_id' => $articleId];
            
        } catch (Exception $e) {
            error_log("Erro ao criar notícia da fazenda: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Função removida - todos os usuários são tratados igualmente no AgroNews
    // O sistema é alimentado pela web, então não precisa de roles diferentes
    
    /**
     * Registrar sincronização
     */
    private function logSync($type, $lactechId, $agronewsId = null, $status = 'success', $error = null) {
        try {
            $pdo = $this->db->getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO agronews_lactech_sync 
                (sync_type, lactech_id, agronews_id, sync_status, error_message) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                agronews_id = VALUES(agronews_id),
                sync_status = VALUES(sync_status),
                error_message = VALUES(error_message),
                last_sync = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([$type, $lactechId, $agronewsId, $status, $error]);
        } catch (Exception $e) {
            error_log("Erro ao registrar sincronização: " . $e->getMessage());
        }
    }
    
    /**
     * Criar slug
     */
    private function createSlug($text) {
        $text = mb_strtolower($text, 'UTF-8');
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        if (strlen($text) > 100) {
            $text = substr($text, 0, 100);
            $text = rtrim($text, '-');
        }
        return $text ?: 'artigo-' . time();
    }
    
    /**
     * Obter estatísticas da fazenda
     */
    public function getFarmStats($farmId = 1) {
        try {
            $stats = $this->db->query("
                SELECT * FROM agronews_farm_stats 
                WHERE farm_id = ? 
                ORDER BY stat_date DESC 
                LIMIT 1
            ", [$farmId]);
            
            return $stats[0] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
}

