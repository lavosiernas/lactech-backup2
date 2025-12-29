<?php
/**
 * API: Dashboard Analítico
 * Endpoint para fornecer dados analíticos e estatísticas
 */

// Iniciar buffer de saída para evitar problemas com headers
if (ob_get_level() == 0) {
    ob_start();
}

require_once __DIR__ . '/../includes/config_login.php';
require_once __DIR__ . '/../includes/Database.class.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Não autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$farm_id = $_SESSION['farm_id'] ?? 1;

function sendResponse($data = null, $message = '', $statusCode = 200) {
    // Limpar qualquer saída anterior
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'success' => $statusCode < 400,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verificar se a conexão foi estabelecida
    if (!$conn) {
        throw new Exception('Não foi possível conectar ao banco de dados');
    }
    
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    if (empty($action)) {
        sendResponse(null, 'Ação não especificada', 400);
    }
    
    $input = [];
    if ($method === 'POST') {
        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }
    } else {
        $input = $_GET;
    }
    
    switch ($action) {
        // ==========================================
        // DASHBOARD PRINCIPAL
        // ==========================================
        case 'dashboard':
            $period = (int)($input['period'] ?? 30); // dias
            
            $stats = [];
            
            // KPIs Principais
            // Produção hoje - tentar volume_records primeiro, depois milk_production
            $today = ['total_volume' => 0, 'animals_count' => 0, 'avg_volume' => 0];
            
            // Verificar se a tabela volume_records existe
            try {
                $stmt = $conn->prepare("
                    SELECT 
                        COALESCE(SUM(total_volume), 0) as total_volume,
                        COALESCE(SUM(total_animals), 0) as animals_count,
                        COALESCE(AVG(average_per_animal), 0) as avg_volume
                    FROM volume_records
                    WHERE farm_id = ? 
                    AND DATE(record_date) = CURDATE()
                ");
                $stmt->execute([$farm_id]);
                $today = $stmt->fetch(PDO::FETCH_ASSOC) ?: $today;
            } catch (PDOException $e) {
                // Tabela volume_records não existe ou erro, usar milk_production
                error_log("Volume_records error: " . $e->getMessage());
            }
            
            // Se não houver dados em volume_records, buscar de milk_production
            if (empty($today['total_volume']) || $today['total_volume'] == 0) {
                try {
                    $stmt = $conn->prepare("
                        SELECT 
                            COALESCE(SUM(volume), 0) as total_volume,
                            COUNT(DISTINCT animal_id) as animals_count,
                            COALESCE(AVG(volume), 0) as avg_volume
                        FROM milk_production
                        WHERE farm_id = ? 
                        AND DATE(production_date) = CURDATE()
                    ");
                    $stmt->execute([$farm_id]);
                    $today_mp = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($today_mp) {
                        $today = $today_mp;
                    }
                } catch (PDOException $e) {
                    error_log("Milk_production error: " . $e->getMessage());
                }
            }
            
            $stats['production_today'] = [
                'total' => (float)($today['total_volume'] ?? 0),
                'animals' => (int)($today['animals_count'] ?? 0),
                'average' => (float)($today['avg_volume'] ?? 0)
            ];
            
            // Produção período - combinar ambas as tabelas
            $period_mp = ['total_volume' => 0, 'animals_count' => 0, 'avg_volume' => 0];
            $period_vr = ['total_volume' => 0, 'animals_count' => 0, 'avg_volume' => 0];
            
            try {
                $stmt = $conn->prepare("
                    SELECT 
                        COALESCE(SUM(volume), 0) as total_volume,
                        COUNT(DISTINCT animal_id) as animals_count,
                        COALESCE(AVG(volume), 0) as avg_volume
                    FROM milk_production
                    WHERE farm_id = ? 
                    AND production_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ");
                $stmt->execute([$farm_id, $period]);
                $period_mp = $stmt->fetch(PDO::FETCH_ASSOC) ?: $period_mp;
            } catch (PDOException $e) {
                error_log("Milk_production period error: " . $e->getMessage());
            }
            
            try {
                $stmt = $conn->prepare("
                    SELECT 
                        COALESCE(SUM(total_volume), 0) as total_volume,
                        COALESCE(SUM(total_animals), 0) as animals_count,
                        COALESCE(AVG(average_per_animal), 0) as avg_volume
                    FROM volume_records
                    WHERE farm_id = ? 
                    AND record_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ");
                $stmt->execute([$farm_id, $period]);
                $period_vr = $stmt->fetch(PDO::FETCH_ASSOC) ?: $period_vr;
            } catch (PDOException $e) {
                error_log("Volume_records period error: " . $e->getMessage());
            }
            
            $stats['production_period'] = [
                'total' => (float)($period_mp['total_volume'] ?? 0) + (float)($period_vr['total_volume'] ?? 0),
                'animals' => max((int)($period_mp['animals_count'] ?? 0), (int)($period_vr['animals_count'] ?? 0)),
                'average' => (float)($period_mp['avg_volume'] ?? 0) > 0 ? (float)($period_mp['avg_volume'] ?? 0) : (float)($period_vr['avg_volume'] ?? 0)
            ];
            
            // Qualidade do Leite
            $quality = ['avg_fat' => 0, 'avg_protein' => 0, 'avg_somatic_cells' => 0];
            try {
                $stmt = $conn->prepare("
                    SELECT 
                        COALESCE(AVG(fat_content), 0) as avg_fat,
                        COALESCE(AVG(protein_content), 0) as avg_protein,
                        COALESCE(AVG(somatic_cells), 0) as avg_somatic_cells
                    FROM milk_production
                    WHERE farm_id = ? 
                    AND production_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ");
                $stmt->execute([$farm_id, $period]);
                $quality = $stmt->fetch(PDO::FETCH_ASSOC) ?: $quality;
            } catch (PDOException $e) {
                error_log("Quality error: " . $e->getMessage());
            }
            $stats['quality'] = [
                'fat' => round((float)($quality['avg_fat'] ?? 0), 2),
                'protein' => round((float)($quality['avg_protein'] ?? 0), 2),
                'somatic_cells' => round((float)($quality['avg_somatic_cells'] ?? 0), 0)
            ];
            
            // Taxa de Prenhez
            $reproduction = ['total_inseminations' => 0, 'pregnancies' => 0, 'pending' => 0];
            try {
                $stmt = $conn->prepare("
                    SELECT 
                        COUNT(*) as total_inseminations,
                        SUM(CASE WHEN result = 'Prenha' THEN 1 ELSE 0 END) as pregnancies,
                        COUNT(CASE WHEN result IS NULL THEN 1 END) as pending
                    FROM inseminations
                    WHERE farm_id = ? 
                    AND insemination_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ");
                $stmt->execute([$farm_id, $period]);
                $reproduction = $stmt->fetch(PDO::FETCH_ASSOC) ?: $reproduction;
            } catch (PDOException $e) {
                error_log("Reproduction error: " . $e->getMessage());
            }
            $total_ins = (int)($reproduction['total_inseminations'] ?? 0);
            $pregnancies = (int)($reproduction['pregnancies'] ?? 0);
            $stats['pregnancy_rate'] = $total_ins > 0 ? round(($pregnancies / $total_ins) * 100, 1) : 0;
            
            // Total de Animais
            $animals = ['total' => 0];
            try {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as total
                    FROM animals
                    WHERE farm_id = ? AND (is_active = 1 OR is_active IS NULL)
                ");
                $stmt->execute([$farm_id]);
                $animals = $stmt->fetch(PDO::FETCH_ASSOC) ?: $animals;
            } catch (PDOException $e) {
                error_log("Animals count error: " . $e->getMessage());
            }
            $stats['total_animals'] = (int)($animals['total'] ?? 0);
            
            sendResponse($stats);
            break;
            
        // ==========================================
        // PRODUÇÃO POR PERÍODO (GRÁFICO)
        // ==========================================
        case 'production_chart':
            $period = (int)($input['period'] ?? 30);
            $group_by = $input['group_by'] ?? 'day'; // day, week, month
            
            $date_display = $group_by === 'day' ? 'DATE(production_date)' : ($group_by === 'week' ? 'YEARWEEK(production_date)' : 'DATE_FORMAT(production_date, "%Y-%m")');
            
            // Buscar dados de milk_production
            $stmt = $conn->prepare("
                SELECT 
                    $date_display as period,
                    COALESCE(SUM(volume), 0) as total_volume,
                    COALESCE(AVG(volume), 0) as avg_volume,
                    COUNT(DISTINCT animal_id) as animals_count
                FROM milk_production
                WHERE farm_id = ? 
                AND production_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY $date_display
                ORDER BY period ASC
            ");
            $stmt->execute([$farm_id, $period]);
            $data_mp = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Buscar dados de volume_records
            $date_display_vr = $group_by === 'day' ? 'DATE(record_date)' : ($group_by === 'week' ? 'YEARWEEK(record_date)' : 'DATE_FORMAT(record_date, "%Y-%m")');
            $stmt = $conn->prepare("
                SELECT 
                    $date_display_vr as period,
                    COALESCE(SUM(total_volume), 0) as total_volume,
                    COALESCE(AVG(average_per_animal), 0) as avg_volume,
                    COALESCE(SUM(total_animals), 0) as animals_count
                FROM volume_records
                WHERE farm_id = ? 
                AND record_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY $date_display_vr
                ORDER BY period ASC
            ");
            $stmt->execute([$farm_id, $period]);
            $data_vr = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Combinar dados por período
            $data = [];
            
            // Adicionar dados de milk_production
            foreach ($data_mp as $row) {
                $period_key = $row['period'];
                if (!isset($data[$period_key])) {
                    $data[$period_key] = [
                        'period' => $period_key,
                        'total_volume' => 0,
                        'avg_volume' => 0,
                        'animals_count' => 0,
                        'count' => 0
                    ];
                }
                $data[$period_key]['total_volume'] += (float)$row['total_volume'];
                $data[$period_key]['avg_volume'] += (float)$row['avg_volume'];
                $data[$period_key]['animals_count'] = max($data[$period_key]['animals_count'], (int)$row['animals_count']);
                $data[$period_key]['count']++;
            }
            
            // Adicionar dados de volume_records
            foreach ($data_vr as $row) {
                $period_key = $row['period'];
                if (!isset($data[$period_key])) {
                    $data[$period_key] = [
                        'period' => $period_key,
                        'total_volume' => 0,
                        'avg_volume' => 0,
                        'animals_count' => 0,
                        'count' => 0
                    ];
                }
                $data[$period_key]['total_volume'] += (float)$row['total_volume'];
                $data[$period_key]['avg_volume'] += (float)$row['avg_volume'];
                $data[$period_key]['animals_count'] = max($data[$period_key]['animals_count'], (int)$row['animals_count']);
                $data[$period_key]['count']++;
            }
            
            // Calcular médias
            foreach ($data as &$row) {
                if ($row['count'] > 0) {
                    $row['avg_volume'] = $row['avg_volume'] / $row['count'];
                }
                unset($row['count']);
            }
            
            // Ordenar e converter para array indexado
            ksort($data);
            $data = array_values($data);
            
            sendResponse($data);
            break;
            
        // ==========================================
        // QUALIDADE DO LEITE (GRÁFICO)
        // ==========================================
        case 'quality_chart':
            $period = (int)($input['period'] ?? 30);
            
            $stmt = $conn->prepare("
                SELECT 
                    DATE(production_date) as date,
                    COALESCE(AVG(fat_content), 0) as avg_fat,
                    COALESCE(AVG(protein_content), 0) as avg_protein,
                    COALESCE(AVG(somatic_cells), 0) as avg_somatic_cells
                FROM milk_production
                WHERE farm_id = ? 
                AND production_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND (fat_content IS NOT NULL OR protein_content IS NOT NULL)
                GROUP BY DATE(production_date)
                ORDER BY date ASC
            ");
            $stmt->execute([$farm_id, $period]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Se não houver dados, retornar array vazio ao invés de null
            if (empty($data)) {
                $data = [];
            }
            
            sendResponse($data);
            break;
            
        // ==========================================
        // COMPARATIVO HISTÓRICO
        // ==========================================
        case 'historical_comparison':
            $periods = $input['periods'] ?? ['current_month', 'last_month', 'last_year'];
            
            $comparison = [];
            
            foreach ($periods as $period) {
                switch ($period) {
                    case 'current_month':
                        $stmt = $conn->prepare("
                            SELECT 
                                SUM(volume) as total_volume,
                                AVG(volume) as avg_volume,
                                COUNT(DISTINCT animal_id) as animals_count
                            FROM milk_production
                            WHERE farm_id = ? 
                            AND MONTH(production_date) = MONTH(CURDATE())
                            AND YEAR(production_date) = YEAR(CURDATE())
                        ");
                        $stmt->execute([$farm_id]);
                        $comparison['current_month'] = $stmt->fetch(PDO::FETCH_ASSOC);
                        break;
                        
                    case 'last_month':
                        $stmt = $conn->prepare("
                            SELECT 
                                SUM(volume) as total_volume,
                                AVG(volume) as avg_volume,
                                COUNT(DISTINCT animal_id) as animals_count
                            FROM milk_production
                            WHERE farm_id = ? 
                            AND MONTH(production_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                            AND YEAR(production_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                        ");
                        $stmt->execute([$farm_id]);
                        $comparison['last_month'] = $stmt->fetch(PDO::FETCH_ASSOC);
                        break;
                        
                    case 'last_year':
                        $stmt = $conn->prepare("
                            SELECT 
                                SUM(volume) as total_volume,
                                AVG(volume) as avg_volume,
                                COUNT(DISTINCT animal_id) as animals_count
                            FROM milk_production
                            WHERE farm_id = ? 
                            AND YEAR(production_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))
                        ");
                        $stmt->execute([$farm_id]);
                        $comparison['last_year'] = $stmt->fetch(PDO::FETCH_ASSOC);
                        break;
                }
            }
            
            sendResponse($comparison);
            break;
            
        // ==========================================
        // MÉTRICAS DE EFICIÊNCIA
        // ==========================================
        case 'efficiency_metrics':
            $period = (int)($input['period'] ?? 30);
            
            $metrics = [];
            
            // Produção por animal
            $stmt = $conn->prepare("
                SELECT 
                    a.id,
                    a.animal_number,
                    a.name,
                    COUNT(mp.id) as production_days,
                    SUM(mp.volume) as total_volume,
                    AVG(mp.volume) as avg_volume,
                    MAX(mp.volume) as max_volume
                FROM animals a
                LEFT JOIN milk_production mp ON a.id = mp.animal_id 
                    AND mp.production_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                WHERE a.farm_id = ? 
                AND a.status = 'Lactante'
                AND (a.is_active = 1 OR a.is_active IS NULL)
                GROUP BY a.id, a.animal_number, a.name
                HAVING production_days > 0
                ORDER BY avg_volume DESC
                LIMIT 20
            ");
            $stmt->execute([$period, $farm_id]);
            $metrics['top_producers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Taxa de concepção
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(*) as total_inseminations,
                    SUM(CASE WHEN result = 'Prenha' THEN 1 ELSE 0 END) as successful,
                    SUM(CASE WHEN result = 'Vazia' THEN 1 ELSE 0 END) as failed
                FROM inseminations
                WHERE farm_id = ? 
                AND insemination_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND result IS NOT NULL
            ");
            $stmt->execute([$farm_id, $period]);
            $conception = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = (int)($conception['total_inseminations'] ?? 0);
            $successful = (int)($conception['successful'] ?? 0);
            $metrics['conception_rate'] = $total > 0 ? round(($successful / $total) * 100, 1) : 0;
            
            // Intervalo entre partos (IEP)
            $stmt = $conn->prepare("
                SELECT 
                    AVG(DATEDIFF(b2.birth_date, b1.birth_date)) as avg_iep
                FROM births b1
                INNER JOIN births b2 ON b1.animal_id = b2.animal_id 
                    AND b2.birth_date > b1.birth_date
                WHERE b1.farm_id = ? 
                AND b1.birth_date >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
            ");
            $stmt->execute([$farm_id]);
            $iep = $stmt->fetch(PDO::FETCH_ASSOC);
            $metrics['avg_iep'] = round((float)($iep['avg_iep'] ?? 0), 0);
            
            sendResponse($metrics);
            break;
            
        // ==========================================
        // DISTRIBUIÇÃO POR RAÇA
        // ==========================================
        case 'breed_distribution':
            $stmt = $conn->prepare("
                SELECT 
                    COALESCE(breed, 'Não informado') as breed,
                    COUNT(*) as count,
                    SUM(CASE WHEN status = 'Lactante' THEN 1 ELSE 0 END) as lactating
                FROM animals
                WHERE farm_id = ? 
                AND (is_active = 1 OR is_active IS NULL)
                GROUP BY breed
                ORDER BY count DESC
            ");
            $stmt->execute([$farm_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Garantir que sempre retorne um array
            if (empty($data)) {
                $data = [];
            }
            
            sendResponse($data);
            break;
            
        // ==========================================
        // ANÁLISE DE TENDÊNCIAS
        // ==========================================
        case 'trends':
            $metric = $input['metric'] ?? 'production'; // production, quality, reproduction
            $period = (int)($input['period'] ?? 90);
            
            $trends = [];
            
            switch ($metric) {
                case 'production':
                    $stmt = $conn->prepare("
                        SELECT 
                            DATE(production_date) as date,
                            SUM(volume) as total_volume,
                            AVG(volume) as avg_volume
                        FROM milk_production
                        WHERE farm_id = ? 
                        AND production_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                        GROUP BY DATE(production_date)
                        ORDER BY date ASC
                    ");
                    $stmt->execute([$farm_id, $period]);
                    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
                case 'quality':
                    $stmt = $conn->prepare("
                        SELECT 
                            DATE(production_date) as date,
                            AVG(fat_content) as avg_fat,
                            AVG(protein_content) as avg_protein
                        FROM milk_production
                        WHERE farm_id = ? 
                        AND production_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                        GROUP BY DATE(production_date)
                        ORDER BY date ASC
                    ");
                    $stmt->execute([$farm_id, $period]);
                    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
            }
            
            sendResponse($trends);
            break;
            
        default:
            sendResponse(null, 'Ação não reconhecida', 400);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Analytics API PDO Error: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Erro no banco de dados',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    error_log("Analytics API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
