<?php
/**
 * CLASSE DE BANCO DE DADOS REFATORADA - LACTECH
 * Sistema robusto e profissional
 * Versão: 2.0.0
 */

require_once __DIR__ . '/config_login.php';

class Database {
    private static $instance = null;
    private $pdo = null;
    private $lastError = null;
    private $queryCache = [];
    
    // Constantes do sistema
    const FARM_ID = 1;
    const FARM_NAME = 'Lagoa do Mato';
    const DEFAULT_USER_ROLE = 'funcionario';
    const CACHE_TTL = 300; // 5 minutos
    
    /**
     * Construtor privado (Singleton)
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Estabelecer conexão com o banco
     */
    private function connect() {
        try {
            $this->pdo = getDatabase();
            
            if (!$this->pdo) {
                throw new Exception("Falha na conexão com banco de dados");
            }
            
        } catch (Exception $e) {
            $this->lastError = "Erro de conexão: " . $e->getMessage();
            throw new Exception($this->lastError);
        }
    }
    
    /**
     * Obter instância única (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obter conexão PDO
     */
    public function getConnection() {
        // Verificar se a conexão ainda está ativa
        try {
            $this->pdo->query('SELECT 1');
        } catch (PDOException $e) {
            // Reconectar se necessário
            $this->connect();
        }
        
        return $this->pdo;
    }
    
    /**
     * Obter último erro
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Verificar se está conectado
     */
    public function isConnected() {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Executar query com tratamento de erro e cache
     */
    public function query($sql, $params = [], $useCache = false, $cacheKey = null) {
        // Gerar chave de cache se não fornecida
        if ($useCache && $cacheKey === null) {
            $cacheKey = md5($sql . serialize($params));
        }
        
        // Verificar cache primeiro
        if ($useCache && isset($this->queryCache[$cacheKey])) {
            $cached = $this->queryCache[$cacheKey];
            if (time() - $cached['timestamp'] < self::CACHE_TTL) {
                return $cached['data'];
            } else {
                unset($this->queryCache[$cacheKey]);
            }
        }
        
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            
            // Armazenar no cache se solicitado
            if ($useCache) {
                $this->queryCache[$cacheKey] = [
                    'data' => $stmt,
                    'timestamp' => time()
                ];
            }
            
            return $stmt;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Erro na query SQL: {$sql} - {$e->getMessage()}");
            throw $e;
        }
    }
    
    /**
     * Query otimizada para listagem com paginação
     */
    public function queryPaginated($sql, $params = [], $page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        
        // Adicionar LIMIT e OFFSET
        $sql .= " LIMIT {$limit} OFFSET {$offset}";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Query otimizada para contagem
     */
    public function count($table, $conditions = [], $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $result = $this->query($sql, $params)->fetch();
        return (int) $result['count'];
    }
    
    /**
     * Limpar cache de queries
     */
    public function clearCache() {
        $this->queryCache = [];
    }
    
    /**
     * LOGIN - Sistema de autenticação
     */
    public function login($email, $password) {
        try {
            // Buscar usuário
            $stmt = $this->query("
                SELECT u.*, f.name as farm_name 
                FROM users u
                LEFT JOIN farms f ON u.farm_id = f.id
                WHERE u.email = ? AND u.is_active = 1
            ", [$email]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false, 
                    'error' => 'Email ou senha incorretos'
                ];
            }
            
            // Verificar senha
            if (!password_verify($password, $user['password'])) {
                return [
                    'success' => false, 
                    'error' => 'Email ou senha incorretos'
                ];
            }
            
            // Remover senha do retorno
            unset($user['password']);
            
            // Atualizar último login
            $this->updateLastLogin($user['id']);
            
            // Log de login bem-sucedido
            error_log("Login realizado: {$email} - Role: {$user['role']}");
            
            return [
                'success' => true, 
                'user' => $user
            ];
            
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Erro no login: {$email} - {$e->getMessage()}");
            
            return [
                'success' => false, 
                'error' => 'Erro interno do servidor'
            ];
        }
    }
    
    /**
     * Obter usuário por ID
     */
    public function getUser($userId) {
        try {
            $stmt = $this->query("
                SELECT u.*, f.name as farm_name 
                FROM users u
                LEFT JOIN farms f ON u.farm_id = f.id
                WHERE u.id = ?
            ", [$userId]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                unset($user['password']); // Remover senha
            }
            
            return $user;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuário: {$userId} - {$e->getMessage()}");
            return null;
        }
    }
    
    /**
     * Verificar se email existe
     */
    public function emailExists($email) {
        try {
            $stmt = $this->query("SELECT id FROM users WHERE email = ?", [$email]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Erro ao verificar email: {$email} - {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Criar usuário
     */
    public function createUser($userData) {
        try {
            // Validar dados obrigatórios
            $required = ['name', 'email', 'password', 'role'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    return [
                        'success' => false,
                        'error' => "Campo '{$field}' é obrigatório"
                    ];
                }
            }
            
            // Verificar se email já existe
            if ($this->emailExists($userData['email'])) {
                return [
                    'success' => false,
                    'error' => 'Email já está em uso'
                ];
            }
            
            // Hash da senha
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Inserir usuário
            $stmt = $this->query("
                INSERT INTO users (name, email, password, role, farm_id, cpf, phone, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
            ", [
                $userData['name'],
                $userData['email'],
                $hashedPassword,
                $userData['role'] ?? self::DEFAULT_USER_ROLE,
                $userData['farm_id'] ?? self::FARM_ID,
                $userData['cpf'] ?? null,
                $userData['phone'] ?? null
            ]);
            
            $userId = $this->getConnection()->lastInsertId();
            
            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'Usuário criado com sucesso'
            ];
            
        } catch (PDOException $e) {
            error_log("Erro ao criar usuário: {$e->getMessage()}");
            
            return [
                'success' => false,
                'error' => 'Erro ao criar usuário'
            ];
        }
    }
    
    /**
     * Atualizar usuário
     */
    public function updateUser($userId, $userData) {
        try {
            $fields = [];
            $values = [];
            
            foreach ($userData as $field => $value) {
                if (in_array($field, ['name', 'email', 'role', 'cpf', 'phone', 'is_active'])) {
                    $fields[] = "{$field} = ?";
                    $values[] = $value;
                } elseif ($field === 'password') {
                    $fields[] = "password = ?";
                    $values[] = password_hash($value, PASSWORD_DEFAULT);
                }
            }
            
            if (empty($fields)) {
                return [
                    'success' => false,
                    'error' => 'Nenhum campo válido para atualizar'
                ];
            }
            
            $fields[] = "updated_at = NOW()";
            $values[] = $userId;
            
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $this->query($sql, $values);
            
            return [
                'success' => true,
                'message' => 'Usuário atualizado com sucesso'
            ];
            
        } catch (PDOException $e) {
            error_log("Erro ao atualizar usuário: {$userId} - {$e->getMessage()}");
            
            return [
                'success' => false,
                'error' => 'Erro ao atualizar usuário'
            ];
        }
    }
    
    /**
     * Atualizar último login
     */
    private function updateLastLogin($userId) {
        try {
            $this->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$userId]);
        } catch (PDOException $e) {
            // Não falhar o login por causa disso
            error_log("Erro ao atualizar último login: {$userId} - {$e->getMessage()}");
        }
    }
    
    /**
     * Criar fazenda
     */
    public function createFarm($farmData) {
        try {
            $stmt = $this->query("
                INSERT INTO farms (name, location, cnpj, owner_name, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ", [
                $farmData['name'],
                $farmData['location'] ?? null,
                $farmData['cnpj'] ?? null,
                $farmData['owner_name'] ?? null
            ]);
            
            return $this->getConnection()->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Erro ao criar fazenda: {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Obter estatísticas do sistema
     */
    public function getSystemStats() {
        try {
            $stats = [];
            
            // Contar usuários
            $stmt = $this->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
            $stats['users'] = $stmt->fetchColumn();
            
            // Contar animais
            if ($this->tableExists('animals')) {
                $stmt = $this->query("SELECT COUNT(*) FROM animals WHERE is_active = 1");
                $stats['animals'] = $stmt->fetchColumn();
            }
            
            // Contar produção do dia
            if ($this->tableExists('milk_production')) {
                $stmt = $this->query("
                    SELECT COUNT(*) FROM milk_production 
                    WHERE DATE(production_date) = CURDATE()
                ");
                $stats['today_production'] = $stmt->fetchColumn();
            }
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Erro ao obter estatísticas: {$e->getMessage()}");
            return [];
        }
    }
    
    /**
     * Verificar se tabela existe
     */
    private function tableExists($tableName) {
        try {
            $stmt = $this->query("SHOW TABLES LIKE ?", [$tableName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Testar conexão
     */
    public function testConnection() {
        try {
            $stmt = $this->query("SELECT VERSION() as version, NOW() as current_time");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'version' => $result['version'],
                'current_time' => $result['current_time'],
                'environment' => 'production',
                'database' => 'lactech_lgmato'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // =====================================================
    // MÉTODOS PARA AS APIs
    // =====================================================
    
    /**
     * Obter todos os animais
     */
    public function getAllAnimals() {
        try {
            $stmt = $this->query("
                SELECT a.*, 
                       f.name as father_name,
                       m.name as mother_name,
                       DATEDIFF(CURDATE(), a.birth_date) as age_days
                FROM animals a
                LEFT JOIN animals f ON a.father_id = f.id
                LEFT JOIN animals m ON a.mother_id = m.id
                WHERE a.is_active = 1
                ORDER BY a.animal_number
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter animal por ID
     */
    public function getAnimalById($id) {
        try {
            $stmt = $this->query("
                SELECT a.*, 
                       f.name as father_name,
                       m.name as mother_name
                FROM animals a
                LEFT JOIN animals f ON a.father_id = f.id
                LEFT JOIN animals m ON a.mother_id = m.id
                WHERE a.id = ?
            ", [$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Obter animais prenhes
     */
    public function getPregnantAnimals() {
        try {
            $stmt = $this->query("
                SELECT a.*, pc.expected_birth, pc.pregnancy_stage
                FROM animals a
                JOIN pregnancy_controls pc ON a.id = pc.animal_id
                WHERE a.is_active = 1 AND pc.expected_birth >= CURDATE()
                ORDER BY pc.expected_birth
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter pedigree do animal
     */
    public function getAnimalPedigree($id) {
        try {
            $stmt = $this->query("
                SELECT * FROM pedigree_records 
                WHERE animal_id = ?
                ORDER BY generation, position
            ", [$id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter produtividade dos animais
     */
    public function getAnimalsProductivity() {
        try {
            $stmt = $this->query("
                SELECT a.*, 
                       AVG(mp.volume) as avg_daily_production,
                       COUNT(mp.id) as production_records
                FROM animals a
                LEFT JOIN milk_production mp ON a.id = mp.animal_id
                WHERE a.is_active = 1 AND a.gender = 'femea'
                GROUP BY a.id
                ORDER BY avg_daily_production DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter prenhezes ativas
     */
    public function getActivePregnancies() {
        try {
            $stmt = $this->query("
                SELECT pc.*, a.animal_number, a.name as animal_name
                FROM pregnancy_controls pc
                JOIN animals a ON pc.animal_id = a.id
                WHERE pc.expected_birth >= CURDATE()
                ORDER BY pc.expected_birth
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter alertas de maternidade ativos
     */
    public function getActiveMaternityAlerts() {
        try {
            $stmt = $this->query("
                SELECT ma.*, a.animal_number, a.name as animal_name
                FROM maternity_alerts ma
                JOIN animals a ON ma.animal_id = a.id
                WHERE ma.is_resolved = 0
                ORDER BY ma.expected_birth
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter performance reprodutiva
     */
    public function getReproductivePerformance() {
        try {
            $stmt = $this->query("
                SELECT 
                    COUNT(DISTINCT a.id) as total_females,
                    COUNT(DISTINCT pc.id) as pregnancies,
                    COUNT(DISTINCT b.id) as births,
                    AVG(DATEDIFF(pc.expected_birth, i.insemination_date)) as avg_gestation_days
                FROM animals a
                LEFT JOIN inseminations i ON a.id = i.animal_id
                LEFT JOIN pregnancy_controls pc ON i.id = pc.insemination_id
                LEFT JOIN births b ON pc.id = b.pregnancy_id
                WHERE a.gender = 'femea' AND a.is_active = 1
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter ciclos de cio por animal
     */
    public function getHeatCyclesByAnimal($animalId) {
        try {
            $stmt = $this->query("
                SELECT * FROM heat_cycles 
                WHERE animal_id = ?
                ORDER BY heat_date DESC
            ", [$animalId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter indicadores de prenhez
     */
    public function getPregnancyIndicators() {
        try {
            $stmt = $this->query("
                SELECT 
                    COUNT(*) as total_pregnancies,
                    SUM(CASE WHEN expected_birth >= CURDATE() THEN 1 ELSE 0 END) as active_pregnancies,
                    SUM(CASE WHEN expected_birth < CURDATE() THEN 1 ELSE 0 END) as overdue_pregnancies,
                    AVG(DATEDIFF(expected_birth, pregnancy_date)) as avg_gestation_days
                FROM pregnancy_controls
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter estatísticas do dashboard
     */
    public function getDashboardStats() {
        try {
            $stats = [];
            
            // Volume de hoje - APENAS dados de hoje (não usar dados antigos)
            $stmt = $this->query("
                SELECT COALESCE(SUM(total_volume), 0) as volume_today 
                FROM volume_records 
                WHERE DATE(record_date) = CURDATE() AND farm_id = 1
            ");
            $stats['volume_today'] = $stmt->fetchColumn() ?: 0;
            
            // Volume do mês - todos os dados do mês atual
            $stmt = $this->query("
                SELECT COALESCE(SUM(total_volume), 0) as volume_month 
                FROM volume_records 
                WHERE MONTH(record_date) = MONTH(CURDATE()) 
                AND farm_id = 1
            ");
            $stats['volume_month'] = $stmt->fetchColumn() ?: 0;
            
            // Volume do ano - todos os dados disponíveis
            $stmt = $this->query("
                SELECT COALESCE(SUM(total_volume), 0) as volume_year 
                FROM volume_records 
                WHERE farm_id = 1
            ");
            $stats['volume_year'] = $stmt->fetchColumn() ?: 0;
            
            // Log para debug
            error_log("Debug Volume Anual: " . $stats['volume_year']);
            
            // Qualidade média (gordura e proteína) - APENAS dados atuais
            $stmt = $this->query("
                SELECT COALESCE(AVG(fat_content), 0) as avg_fat, 
                       COALESCE(AVG(protein_content), 0) as avg_protein 
                FROM milk_production 
                WHERE DATE(production_date) = CURDATE() AND farm_id = 1 
                AND fat_content IS NOT NULL AND protein_content IS NOT NULL
            ");
            $quality = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['avg_fat'] = $quality['avg_fat'] ?: 0; // Zero se não há dados de hoje
            $stats['avg_protein'] = $quality['avg_protein'] ?: 0; // Zero se não há dados de hoje
            
            // Pagamentos pendentes - usando dados reais
            $stmt = $this->query("
                SELECT COALESCE(SUM(amount), 0) as pending_payments 
                FROM financial_records 
                WHERE type = 'despesa' AND farm_id = 1
                AND record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $stats['pending_payments'] = $stmt->fetchColumn() ?: 0;
            
            // Usuários ativos - total de usuários do sistema
            $stmt = $this->query("
                SELECT COUNT(*) as active_users 
                FROM users 
                WHERE farm_id = 1
            ");
            $stats['active_users'] = $stmt->fetchColumn() ?: 0;
            
            // Debug: verificar usuários no banco
            $stmt = $this->query("
                SELECT COUNT(*) as total_users, 
                       COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_count,
                       COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_count
                FROM users 
                WHERE farm_id = 1
            ");
            $userDebug = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Debug Usuários - Total: {$userDebug['total_users']}, Ativos: {$userDebug['active_count']}, Inativos: {$userDebug['inactive_count']}");
            
            // Animais totais - usando dados reais
            $stmt = $this->query("
                SELECT COUNT(*) as total_animals 
                FROM animals 
                WHERE is_active = 1 AND farm_id = 1
            ");
            $stats['total_animals'] = $stmt->fetchColumn() ?: 0;
            
            // Prenhezes ativas - usando dados reais
            $stmt = $this->query("
                SELECT COUNT(*) as active_pregnancies 
                FROM pregnancy_controls 
                WHERE expected_birth >= CURDATE() AND farm_id = 1
            ");
            $stats['active_pregnancies'] = $stmt->fetchColumn() ?: 0;
            
            // Alertas ativos - usando dados reais
            $stmt = $this->query("
                SELECT COUNT(*) as active_alerts 
                FROM health_alerts 
                WHERE is_resolved = 0 AND farm_id = 1
            ");
            $stats['active_alerts'] = $stmt->fetchColumn() ?: 0;
            
            // Log para debug
            error_log("Dashboard Stats (APENAS dados atuais): " . json_encode($stats));
            
            // Debug: verificar dados disponíveis
            $debug_stmt = $this->query("
                SELECT 
                    MIN(production_date) as min_date,
                    MAX(production_date) as max_date,
                    COUNT(*) as total_records,
                    SUM(volume) as total_volume,
                    CURDATE() as current_date
                FROM milk_production 
                WHERE farm_id = 1
            ");
            $debug_data = $debug_stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Debug - Dados disponíveis no banco: " . json_encode($debug_data));
            error_log("Debug - Data atual do sistema: " . date('Y-m-d'));
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Erro no getDashboardStats: " . $e->getMessage());
            return [
                'volume_today' => 0,
                'volume_month' => 0,
                'avg_fat' => 0,
                'avg_protein' => 0,
                'pending_payments' => 0,
                'active_users' => 0,
                'total_animals' => 0,
                'active_pregnancies' => 0,
                'active_alerts' => 0
            ];
        }
    }
    
    /**
     * Obter usuários da fazenda
     */
    public function getUsersByFarm($farmId) {
        try {
            $stmt = $this->query("
                SELECT id, name, email, role, is_active, last_login
                FROM users 
                WHERE farm_id = ? AND is_active = 1
                ORDER BY name
            ", [$farmId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter registros de volume
     */
    public function getVolumeRecords($dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT vr.*, u.name as recorded_by_name 
                    FROM volume_records vr 
                    LEFT JOIN users u ON vr.recorded_by = u.id 
                    WHERE 1=1";
            $params = [];
            
            if ($dateFrom) {
                $sql .= " AND vr.record_date >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND vr.record_date <= ?";
                $params[] = $dateTo;
            }
            
            $sql .= " ORDER BY vr.record_date DESC";
            
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter testes de qualidade
     */
    public function getQualityTests($dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT * FROM quality_tests WHERE 1=1";
            $params = [];
            
            if ($dateFrom) {
                $sql .= " AND test_date >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND test_date <= ?";
                $params[] = $dateTo;
            }
            
            $sql .= " ORDER BY test_date DESC";
            
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter registros financeiros
     */
    public function getFinancialRecords($type = null) {
        try {
            $sql = "SELECT * FROM financial_records WHERE 1=1";
            $params = [];
            
            if ($type) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY record_date DESC";
            
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Adicionar registro de volume
     */
    public function addVolumeRecord($data) {
        try {
            // Se tem producer_id (animal_id), inserir na tabela milk_production (individual por vaca)
            if (isset($data['producer_id']) && $data['producer_id']) {
                $stmt = $this->query("
                    INSERT INTO milk_production (animal_id, production_date, shift, volume, temperature, notes, recorded_by, farm_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $data['producer_id'],
                    $data['collection_date'] ?? date('Y-m-d'),
                    $data['period'] ?? 'manha',
                    $data['volume'],
                    $data['temperature'] ?? null,
                    $data['notes'] ?? null,
                    $data['recorded_by'] ?? 1,
                    self::FARM_ID
                ]);
            } else {
                // Se não tem producer_id, inserir na tabela volume_records (geral da fazenda)
                $stmt = $this->query("
                    INSERT INTO volume_records (record_date, shift, total_volume, total_animals, average_per_animal, notes, recorded_by, farm_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $data['collection_date'] ?? date('Y-m-d'),
                    $data['period'] ?? 'manha',
                    $data['volume'],
                    1, // total_animals
                    $data['volume'], // average_per_animal
                    $data['notes'] ?? null,
                    $data['recorded_by'] ?? 1,
                    self::FARM_ID
                ]);
            }
            
            return [
                'success' => true,
                'id' => $this->getConnection()->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Deletar registro de volume
     */
    public function deleteVolumeRecord($id) {
        try {
            $stmt = $this->query("DELETE FROM volume_records WHERE id = ?", [$id]);
            
            return [
                'success' => true,
                'message' => 'Registro de volume excluído com sucesso'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Buscar registros de produção individual por vaca
     */
    public function getMilkProductionRecords($animal_id = null, $date_from = null, $date_to = null) {
        try {
            $sql = "
                SELECT 
                    mp.*,
                    a.animal_number,
                    a.name as animal_name,
                    u.name as recorded_by_name
                FROM milk_production mp
                LEFT JOIN animals a ON mp.animal_id = a.id
                LEFT JOIN users u ON mp.recorded_by = u.id
                WHERE mp.farm_id = ?
            ";
            
            $params = [self::FARM_ID];
            
            if ($animal_id) {
                $sql .= " AND mp.animal_id = ?";
                $params[] = $animal_id;
            }
            
            if ($date_from) {
                $sql .= " AND mp.production_date >= ?";
                $params[] = $date_from;
            }
            
            if ($date_to) {
                $sql .= " AND mp.production_date <= ?";
                $params[] = $date_to;
            }
            
            $sql .= " ORDER BY mp.production_date DESC, mp.created_at DESC";
            
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Adicionar teste de qualidade
     */
    public function addQualityTest($data) {
        try {
            $stmt = $this->query("
                INSERT INTO quality_tests (test_date, test_type, fat_content, protein_content, somatic_cells, recorded_by, farm_id)
                VALUES (?, 'qualidade_leite', ?, ?, ?, ?, ?)
            ", [
                $data['test_date'] ?? date('Y-m-d'),
                $data['fat_percentage'] ?? null,
                $data['protein_percentage'] ?? null,
                $data['ccs'] ?? null,
                $data['tested_by'] ?? 1,
                self::FARM_ID
            ]);
            
            return [
                'success' => true,
                'id' => $this->getConnection()->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Adicionar registro financeiro
     */
    public function addFinancialRecord($data) {
        try {
            $stmt = $this->query("
                INSERT INTO financial_records (record_date, type, category, description, amount, created_by, farm_id)
                VALUES (?, ?, 'Geral', ?, ?, ?, ?)
            ", [
                $data['due_date'] ?? date('Y-m-d'),
                $data['type'],
                $data['description'] ?? '',
                $data['amount'],
                $data['created_by'] ?? 1,
                self::FARM_ID
            ]);
            
            return [
                'success' => true,
                'id' => $this->getConnection()->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Desativar usuário
     */
    public function deactivateUser($userId) {
        try {
            $this->query("UPDATE users SET is_active = 0 WHERE id = ?", [$userId]);
            return ['success' => true, 'message' => 'Usuário desativado'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obter todos os usuários
     */
    public function getAllUsers() {
        try {
            $stmt = $this->query("
                SELECT u.id, u.name, u.email, u.role, u.is_active, u.last_login, u.created_at,
                       f.name as farm_name
                FROM users u
                LEFT JOIN farms f ON u.farm_id = f.id
                ORDER BY u.name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter usuário por ID
     */
    public function getUserById($id) {
        try {
            $stmt = $this->query("
                SELECT u.id, u.name, u.email, u.role, u.cpf, u.phone, u.address, 
                       u.hire_date, u.salary, u.is_active, u.last_login, u.created_at,
                       f.name as farm_name
                FROM users u
                LEFT JOIN farms f ON u.farm_id = f.id
                WHERE u.id = ?
            ", [$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Obter usuários ativos
     */
    public function getActiveUsers() {
        try {
            $stmt = $this->query("
                SELECT u.id, u.name, u.email, u.role, u.last_login, u.created_at,
                       f.name as farm_name
                FROM users u
                LEFT JOIN farms f ON u.farm_id = f.id
                WHERE u.is_active = 1
                ORDER BY u.name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter registro financeiro por ID
     */
    public function getFinancialRecordById($id) {
        try {
            $stmt = $this->query("
                SELECT fr.*, u.name as created_by_name
                FROM financial_records fr
                LEFT JOIN users u ON fr.created_by = u.id
                WHERE fr.id = ?
            ", [$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Obter registro de volume por ID
     */
    public function getVolumeRecordById($id) {
        try {
            $stmt = $this->query("
                SELECT vr.*, u.name as recorded_by_name
                FROM volume_records vr
                LEFT JOIN users u ON vr.recorded_by = u.id
                WHERE vr.id = ?
            ", [$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Obter registro de volume por data
     */
    public function getVolumeRecordsByDate($dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT vr.*, u.name as recorded_by_name 
                    FROM volume_records vr 
                    LEFT JOIN users u ON vr.recorded_by = u.id 
                    WHERE 1=1";
            $params = [];
            
            if ($dateFrom) {
                $sql .= " AND vr.record_date >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND vr.record_date <= ?";
                $params[] = $dateTo;
            }
            
            $sql .= " ORDER BY vr.record_date DESC";
            
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter teste de qualidade por ID
     */
    public function getQualityTestById($id) {
        try {
            $stmt = $this->query("
                SELECT qt.*, u.name as recorded_by_name
                FROM quality_tests qt
                LEFT JOIN users u ON qt.recorded_by = u.id
                WHERE qt.id = ?
            ", [$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Obter todas as inseminações
     */
    public function getAllInseminations() {
        try {
            $stmt = $this->query("
                SELECT i.*, a.animal_number, a.name as animal_name, a.breed,
                       b.bull_number, b.name as bull_name,
                       u.name as recorded_by_name
                FROM inseminations i
                LEFT JOIN animals a ON i.animal_id = a.id
                LEFT JOIN bulls b ON i.bull_id = b.id
                LEFT JOIN users u ON i.recorded_by = u.id
                ORDER BY i.insemination_date DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter inseminações por animal
     */
    public function getInseminationsByAnimal($animalId) {
        try {
            $stmt = $this->query("
                SELECT i.*, b.bull_number, b.name as bull_name,
                       u.name as recorded_by_name
                FROM inseminations i
                LEFT JOIN bulls b ON i.bull_id = b.id
                LEFT JOIN users u ON i.recorded_by = u.id
                WHERE i.animal_id = ?
                ORDER BY i.insemination_date DESC
            ", [$animalId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obter inseminações recentes
     */
    public function getRecentInseminations($limit = 10) {
        try {
            $stmt = $this->query("
                SELECT i.*, a.animal_number, a.name as animal_name,
                       b.bull_number, b.name as bull_name,
                       u.name as recorded_by_name
                FROM inseminations i
                LEFT JOIN animals a ON i.animal_id = a.id
                LEFT JOIN bulls b ON i.bull_id = b.id
                LEFT JOIN users u ON i.recorded_by = u.id
                ORDER BY i.insemination_date DESC
                LIMIT ?
            ", [$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Criar inseminação
     */
    public function createInsemination($data) {
        try {
            $stmt = $this->query("
                INSERT INTO inseminations (animal_id, bull_id, insemination_date, insemination_type, technician, notes, recorded_by, farm_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $data['animal_id'],
                $data['bull_id'] ?? null,
                $data['insemination_date'] ?? date('Y-m-d'),
                $data['insemination_type'] ?? 'inseminacao_artificial',
                $data['technician'] ?? null,
                $data['notes'] ?? null,
                $data['recorded_by'] ?? 1,
                self::FARM_ID
            ]);
            
            return [
                'success' => true,
                'id' => $this->getConnection()->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar inseminação
     */
    public function updateInsemination($id, $data) {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, ['animal_id', 'bull_id', 'insemination_date', 'insemination_type', 'technician', 'notes'])) {
                    $fields[] = "{$field} = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return [
                    'success' => false,
                    'error' => 'Nenhum campo válido para atualizar'
                ];
            }
            
            $fields[] = "updated_at = NOW()";
            $values[] = $id;
            
            $sql = "UPDATE inseminations SET " . implode(', ', $fields) . " WHERE id = ?";
            $this->query($sql, $values);
            
            return [
                'success' => true,
                'message' => 'Inseminação atualizada com sucesso'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Deletar inseminação
     */
    public function deleteInsemination($id) {
        try {
            $this->query("DELETE FROM inseminations WHERE id = ?", [$id]);
            return [
                'success' => true,
                'message' => 'Inseminação excluída com sucesso'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Criar animal
     */
    public function createAnimal($data) {
        try {
            // Debug: Log dos dados recebidos
            error_log("DEBUG createAnimal - Dados recebidos: " . json_encode($data));
            
            // Validar campos obrigatórios
            if (empty($data['animal_number'])) {
                return [
                    'success' => false,
                    'error' => 'Número do animal é obrigatório'
                ];
            }
            
            if (empty($data['breed'])) {
                return [
                    'success' => false,
                    'error' => 'Raça é obrigatória'
                ];
            }
            
            if (empty($data['gender'])) {
                return [
                    'success' => false,
                    'error' => 'Sexo é obrigatório'
                ];
            }
            
            if (empty($data['birth_date'])) {
                return [
                    'success' => false,
                    'error' => 'Data de nascimento é obrigatória'
                ];
            }
            
            $stmt = $this->query("
                INSERT INTO animals (
                    animal_number, 
                    name, 
                    breed, 
                    gender, 
                    birth_date, 
                    birth_weight, 
                    father_id, 
                    mother_id, 
                    status, 
                    health_status, 
                    reproductive_status, 
                    entry_date, 
                    farm_id, 
                    notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $data['animal_number'],
                $data['name'] ?? null,
                $data['breed'],
                $data['gender'],
                $data['birth_date'],
                $data['birth_weight'] ?? null,
                $data['father_id'] ?? null,
                $data['mother_id'] ?? null,
                $data['status'] ?? 'Bezerra',
                $data['health_status'] ?? 'saudavel',
                $data['reproductive_status'] ?? 'vazia',
                $data['entry_date'] ?? date('Y-m-d'),
                self::FARM_ID,
                $data['notes'] ?? null
            ]);
            
            $insertId = $this->getConnection()->lastInsertId();
            error_log("DEBUG createAnimal - Animal inserido com ID: " . $insertId);
            
            return [
                'success' => true,
                'id' => $insertId,
                'message' => 'Animal cadastrado com sucesso'
            ];
        } catch (PDOException $e) {
            error_log("DEBUG createAnimal - Erro PDO: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar animal
     */
    public function updateAnimal($id, $data) {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, ['animal_number', 'name', 'birth_date', 'breed', 'gender', 'status', 'origin', 'father_id', 'mother_id', 'notes'])) {
                    $fields[] = "{$field} = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return [
                    'success' => false,
                    'error' => 'Nenhum campo válido para atualizar'
                ];
            }
            
            $fields[] = "updated_at = NOW()";
            $values[] = $id;
            
            $sql = "UPDATE animals SET " . implode(', ', $fields) . " WHERE id = ?";
            $this->query($sql, $values);
            
            return [
                'success' => true,
                'message' => 'Animal atualizado com sucesso'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Deletar animal
     */
    public function deleteAnimal($id) {
        try {
            $this->query("DELETE FROM animals WHERE id = ?", [$id]);
            return [
                'success' => true,
                'message' => 'Animal excluído com sucesso'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Destructor - fechar conexão
     */
    public function __destruct() {
        $this->pdo = null;
    }
}
?>