<?php
/**
 * API: AI Engine
 * Motor de Inteligência Artificial para previsões e automações
 * Diferencial competitivo sobre FarmTell Milk
 */

// Limpar qualquer output anterior
ob_start();
ob_clean();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Desabilitar exibição de erros

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada']);
    exit;
}

require_once $dbPath;

function sendResponse($data = null, $error = null, $status = 200) {
    http_response_code($status);
    echo json_encode([
        'success' => $error === null,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

/**
 * Algoritmo de Previsão de Cio
 * Baseado em histórico de ciclos + padrões comportamentais
 */
function predictHeatCycle($db, $animal_id) {
    // Buscar histórico de cios
    $stmt = $db->query("
        SELECT 
            heat_date,
            heat_intensity,
            insemination_planned
        FROM heat_cycles
        WHERE animal_id = ?
        ORDER BY heat_date DESC
        LIMIT 10
    ", [$animal_id]);
    
    $cycles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($cycles) < 2) {
        return [
            'success' => false,
            'message' => 'Dados insuficientes. Mínimo de 2 ciclos necessários.',
            'confidence' => 0
        ];
    }
    
    // Calcular intervalos entre cios
    $intervals = [];
    for ($i = 0; $i < count($cycles) - 1; $i++) {
        $date1 = new DateTime($cycles[$i]['heat_date']);
        $date2 = new DateTime($cycles[$i + 1]['heat_date']);
        $diff = $date2->diff($date1)->days;
        $intervals[] = $diff;
    }
    
    // Média e desvio padrão
    $avg_interval = array_sum($intervals) / count($intervals);
    $variance = 0;
    foreach ($intervals as $interval) {
        $variance += pow($interval - $avg_interval, 2);
    }
    $std_dev = sqrt($variance / count($intervals));
    
    // Calcular confiança baseada na consistência
    $consistency = max(0, 100 - ($std_dev * 5)); // Quanto menor desvio, maior confiança
    
    // Ciclo normal é 21 dias (18-24)
    $is_regular = $avg_interval >= 18 && $avg_interval <= 24;
    
    if ($is_regular) {
        $confidence = min(95, $consistency + 10); // Bônus para ciclo regular
    } else {
        $confidence = max(50, $consistency - 10); // Penalidade para irregular
    }
    
    // Prever próximo cio
    $last_heat = new DateTime($cycles[0]['heat_date']);
    $predicted_date = clone $last_heat;
    $predicted_date->add(new DateInterval('P' . round($avg_interval) . 'D'));
    
    // Janela de previsão (±2 dias)
    $window_start = clone $predicted_date;
    $window_start->sub(new DateInterval('P2D'));
    $window_end = clone $predicted_date;
    $window_end->add(new DateInterval('P2D'));
    
    // Salvar previsão no banco
    try {
        $db->query("
            INSERT INTO ai_predictions (
                animal_id, prediction_type, predicted_date, confidence_score,
                algorithm_version, input_data, farm_id
            ) VALUES (?, 'heat', ?, ?, 'v2.0', ?, 1)
        ", [
            $animal_id,
            $predicted_date->format('Y-m-d'),
            $confidence,
            json_encode([
                'avg_interval' => $avg_interval,
                'std_dev' => $std_dev,
                'cycles_analyzed' => count($cycles),
                'is_regular' => $is_regular,
                'last_cycles' => $intervals
            ])
        ]);
    } catch (Exception $e) {
        error_log("Erro ao salvar previsão de cio: " . $e->getMessage());
    }
    
    return [
        'success' => true,
        'predicted_date' => $predicted_date->format('Y-m-d'),
        'window_start' => $window_start->format('Y-m-d'),
        'window_end' => $window_end->format('Y-m-d'),
        'confidence' => round($confidence, 1),
        'avg_interval' => round($avg_interval, 1),
        'is_regular' => $is_regular,
        'cycles_analyzed' => count($cycles),
        'last_heat' => $cycles[0]['heat_date'],
        'recommendation' => generateHeatRecommendation($avg_interval, $confidence)
    ];
}

/**
 * Algoritmo de Previsão de Produção
 * Machine Learning simples baseado em tendências
 */
function predictProduction($db, $animal_id, $days_ahead = 7) {
    // Buscar produção dos últimos 30 dias
    $stmt = $db->query("
        SELECT 
            production_date,
            SUM(volume) as daily_volume
        FROM milk_production
        WHERE animal_id = ?
          AND production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY production_date
        ORDER BY production_date ASC
    ", [$animal_id]);
    
    $production = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($production) < 7) {
        return [
            'success' => false,
            'message' => 'Dados insuficientes. Mínimo de 7 dias necessários.',
            'confidence' => 0
        ];
    }
    
    // Calcular média móvel e tendência
    $volumes = array_column($production, 'daily_volume');
    $avg_volume = array_sum($volumes) / count($volumes);
    
    // Calcular tendência (regressão linear simples)
    $n = count($volumes);
    $sum_x = 0;
    $sum_y = 0;
    $sum_xy = 0;
    $sum_xx = 0;
    
    foreach ($volumes as $i => $y) {
        $x = $i + 1;
        $sum_x += $x;
        $sum_y += $y;
        $sum_xy += $x * $y;
        $sum_xx += $x * $x;
    }
    
    $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
    $intercept = ($sum_y - $slope * $sum_x) / $n;
    
    // Prever próximos dias
    $predictions = [];
    for ($day = 1; $day <= $days_ahead; $day++) {
        $x = $n + $day;
        $predicted_volume = $intercept + ($slope * $x);
        $predicted_volume = max(0, $predicted_volume); // Não pode ser negativo
        
        $predictions[] = [
            'date' => date('Y-m-d', strtotime("+$day days")),
            'predicted_volume' => round($predicted_volume, 2),
            'trend' => $slope > 0.1 ? 'increasing' : ($slope < -0.1 ? 'decreasing' : 'stable')
        ];
    }
    
    // Calcular variação (para confiança)
    $variance = 0;
    foreach ($volumes as $i => $actual) {
        $predicted = $intercept + ($slope * ($i + 1));
        $variance += pow($actual - $predicted, 2);
    }
    $mse = $variance / $n;
    $rmse = sqrt($mse);
    
    // Confiança baseada no erro
    $confidence = max(50, min(95, 100 - ($rmse / $avg_volume * 100)));
    
    // Salvar previsões
    foreach ($predictions as $pred) {
        try {
            $db->query("
                INSERT INTO ai_predictions (
                    animal_id, prediction_type, predicted_date, predicted_value,
                    confidence_score, algorithm_version, input_data, farm_id
                ) VALUES (?, 'production', ?, ?, ?, 'v2.0', ?, 1)
            ", [
                $animal_id,
                $pred['date'],
                $pred['predicted_volume'],
                $confidence,
                json_encode([
                    'days_analyzed' => $n,
                    'avg_volume' => $avg_volume,
                    'trend_slope' => $slope,
                    'rmse' => $rmse
                ])
            ]);
        } catch (Exception $e) {
            error_log("Erro ao salvar previsão de produção: " . $e->getMessage());
        }
    }
    
    return [
        'success' => true,
        'predictions' => $predictions,
        'confidence' => round($confidence, 1),
        'avg_volume' => round($avg_volume, 2),
        'trend' => $slope > 0.1 ? 'increasing' : ($slope < -0.1 ? 'decreasing' : 'stable'),
        'trend_percentage' => round(($slope / $avg_volume) * 100, 2),
        'days_analyzed' => $n,
        'recommendation' => generateProductionRecommendation($slope, $avg_volume)
    ];
}

/**
 * Detector de Anomalias em Produção
 * Identifica quedas ou aumentos anormais
 */
function detectProductionAnomalies($db, $animal_id) {
    // Buscar produção dos últimos 14 dias
    $stmt = $db->query("
        SELECT 
            production_date,
            SUM(volume) as daily_volume
        FROM milk_production
        WHERE animal_id = ?
          AND production_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        GROUP BY production_date
        ORDER BY production_date DESC
    ", [$animal_id]);
    
    $production = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($production) < 5) {
        return ['anomalies' => [], 'message' => 'Dados insuficientes'];
    }
    
    $volumes = array_column($production, 'daily_volume');
    $avg = array_sum($volumes) / count($volumes);
    
    // Calcular desvio padrão
    $variance = 0;
    foreach ($volumes as $vol) {
        $variance += pow($vol - $avg, 2);
    }
    $std_dev = sqrt($variance / count($volumes));
    
    // Detectar anomalias (valores fora de 2 desvios padrão)
    $anomalies = [];
    foreach ($production as $prod) {
        $volume = $prod['daily_volume'];
        $z_score = abs(($volume - $avg) / $std_dev);
        
        if ($z_score > 2) { // Anomalia significativa
            $type = $volume < $avg ? 'drop' : 'spike';
            $severity = $z_score > 3 ? 'critical' : 'warning';
            
            $anomalies[] = [
                'date' => $prod['production_date'],
                'volume' => $volume,
                'expected' => round($avg, 2),
                'deviation' => round($volume - $avg, 2),
                'deviation_percent' => round((($volume - $avg) / $avg) * 100, 1),
                'z_score' => round($z_score, 2),
                'type' => $type,
                'severity' => $severity
            ];
        }
    }
    
    return [
        'anomalies' => $anomalies,
        'avg_volume' => round($avg, 2),
        'std_dev' => round($std_dev, 2),
        'days_analyzed' => count($production)
    ];
}

/**
 * Recomendações Automáticas Baseadas em Dados
 */
function generateSmartRecommendations($db, $animal_id) {
    $recommendations = [];
    $animal = $db->query("SELECT * FROM animals WHERE id = ?", [$animal_id])->fetch(PDO::FETCH_ASSOC);
    
    if (!$animal) {
        return ['recommendations' => [], 'error' => 'Animal não encontrado'];
    }
    
    // 1. Verificar último BCS
    $bcs = $db->query("
        SELECT score, evaluation_date, DATEDIFF(CURDATE(), evaluation_date) as days_ago
        FROM body_condition_scores
        WHERE animal_id = ?
        ORDER BY evaluation_date DESC
        LIMIT 1
    ", [$animal_id])->fetch(PDO::FETCH_ASSOC);
    
    if ($bcs) {
        if ($bcs['score'] < 2.5) {
            $recommendations[] = [
                'type' => 'nutrition',
                'priority' => 'high',
                'title' => 'BCS Baixo Detectado',
                'message' => "Animal com BCS {$bcs['score']} (abaixo do ideal). Aumentar concentrado em 1-2 kg/dia.",
                'action' => 'increase_feed'
            ];
        }
        
        if ($bcs['days_ago'] > 30) {
            $recommendations[] = [
                'type' => 'health',
                'priority' => 'medium',
                'title' => 'Avaliação de BCS Atrasada',
                'message' => "Última avaliação há {$bcs['days_ago']} dias. Recomendado avaliar mensalmente.",
                'action' => 'schedule_bcs_check'
            ];
        }
    } else {
        $recommendations[] = [
            'type' => 'health',
            'priority' => 'high',
            'title' => 'Sem Avaliação de BCS',
            'message' => 'Animal nunca foi avaliado. Fazer avaliação de condição corporal.',
            'action' => 'first_bcs_check'
        ];
    }
    
    // 2. Analisar produção
    $prod = $db->query("
        SELECT 
            AVG(volume) as avg_volume,
            MAX(volume) as max_volume,
            MIN(volume) as min_volume,
            COUNT(*) as days_count
        FROM milk_production
        WHERE animal_id = ?
          AND production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ", [$animal_id])->fetch(PDO::FETCH_ASSOC);
    
    if ($prod && $prod['days_count'] > 0) {
        $variation = (($prod['max_volume'] - $prod['min_volume']) / $prod['avg_volume']) * 100;
        
        if ($variation > 30) {
            $recommendations[] = [
                'type' => 'production',
                'priority' => 'medium',
                'title' => 'Produção Instável',
                'message' => "Variação de {$variation}% na última semana. Verificar saúde e alimentação.",
                'action' => 'check_health'
            ];
        }
        
        // Detectar queda brusca
        $recent = $db->query("
            SELECT AVG(volume) as recent_avg
            FROM milk_production
            WHERE animal_id = ? AND production_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
        ", [$animal_id])->fetch(PDO::FETCH_ASSOC);
        
        $old = $db->query("
            SELECT AVG(volume) as old_avg
            FROM milk_production
            WHERE animal_id = ? 
              AND production_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ", [$animal_id])->fetch(PDO::FETCH_ASSOC);
        
        if ($recent && $old && $old['old_avg'] > 0) {
            $drop = (($old['old_avg'] - $recent['recent_avg']) / $old['old_avg']) * 100;
            
            if ($drop > 20) {
                $recommendations[] = [
                    'type' => 'alert',
                    'priority' => 'high',
                    'title' => 'Queda de Produção Detectada',
                    'message' => "Queda de {$drop}% nos últimos 3 dias. Investigar: mastite, cio, estresse.",
                    'action' => 'veterinary_check'
                ];
            }
        }
    }
    
    // 3. Verificar intervalo de inseminações
    $last_insem = $db->query("
        SELECT insemination_date, DATEDIFF(CURDATE(), insemination_date) as days_ago
        FROM inseminations
        WHERE animal_id = ?
        ORDER BY insemination_date DESC
        LIMIT 1
    ", [$animal_id])->fetch(PDO::FETCH_ASSOC);
    
    if ($last_insem && $last_insem['days_ago'] > 45 && $animal['reproductive_status'] === 'vazia') {
        $recommendations[] = [
            'type' => 'reproduction',
            'priority' => 'medium',
            'title' => 'Tempo sem Inseminação',
            'message' => "Animal vazio há {$last_insem['days_ago']} dias. Observar cio para nova inseminação.",
            'action' => 'monitor_heat'
        ];
    }
    
    // 4. Verificar prenhez próxima do parto
    $pregnancy = $db->query("
        SELECT expected_birth, DATEDIFF(expected_birth, CURDATE()) as days_until
        FROM pregnancy_controls
        WHERE animal_id = ? AND expected_birth >= CURDATE()
        ORDER BY expected_birth ASC
        LIMIT 1
    ", [$animal_id])->fetch(PDO::FETCH_ASSOC);
    
    if ($pregnancy) {
        if ($pregnancy['days_until'] <= 30 && $pregnancy['days_until'] > 14) {
            $recommendations[] = [
                'type' => 'reproduction',
                'priority' => 'high',
                'title' => 'Preparar para Parto',
                'message' => "Parto previsto em {$pregnancy['days_until']} dias. Secar animal e preparar maternidade.",
                'action' => 'dry_off'
            ];
        } elseif ($pregnancy['days_until'] <= 14) {
            $recommendations[] = [
                'type' => 'reproduction',
                'priority' => 'urgent',
                'title' => 'Parto Iminente',
                'message' => "Parto em {$pregnancy['days_until']} dias. Monitorar diariamente e preparar parto.",
                'action' => 'prepare_calving'
            ];
        }
    }
    
    // 5. Verificar tratamentos pendentes
    $pending = $db->query("
        SELECT record_type, medication, next_date, DATEDIFF(next_date, CURDATE()) as days_until
        FROM health_records
        WHERE animal_id = ? AND next_date >= CURDATE()
        ORDER BY next_date ASC
        LIMIT 3
    ", [$animal_id])->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($pending as $treat) {
        if ($treat['days_until'] <= 3) {
            $recommendations[] = [
                'type' => 'health',
                'priority' => $treat['days_until'] == 0 ? 'urgent' : 'high',
                'title' => "{$treat['record_type']} Pendente",
                'message' => "{$treat['medication']} vence em {$treat['days_until']} dias.",
                'action' => 'apply_medication'
            ];
        }
    }
    
    return [
        'animal_number' => $animal['animal_number'],
        'animal_name' => $animal['name'],
        'total_recommendations' => count($recommendations),
        'recommendations' => $recommendations
    ];
}

function generateHeatRecommendation($avg_interval, $confidence) {
    if ($avg_interval < 18) {
        return "Ciclo curto (<18 dias). Possível problema hormonal. Consultar veterinário.";
    } elseif ($avg_interval > 24) {
        return "Ciclo longo (>24 dias). Pode indicar cio silencioso. Aumentar observação.";
    } elseif ($confidence >= 85) {
        return "Ciclo regular e previsível. Preparar inseminação na janela prevista.";
    } else {
        return "Ciclo irregular. Aumentar frequência de observação de cio.";
    }
}

function generateProductionRecommendation($slope, $avg_volume) {
    if ($slope > 0.5) {
        return "Produção em alta! Manter protocolo nutricional atual.";
    } elseif ($slope < -0.5) {
        return "Queda de produção detectada. Verificar: saúde, alimentação, estresse.";
    } else {
        return "Produção estável. Continue monitorando.";
    }
}

// ============================================================
// ENDPOINTS DA API
// ============================================================

try {
    $db = Database::getInstance();
    
    // Verificar se tabelas necessárias existem
    $tables_check = $db->query("SHOW TABLES LIKE 'ai_predictions'")->fetch();
    if (!$tables_check) {
        sendResponse(null, 'Tabela ai_predictions não encontrada. Execute o SQL de upgrade primeiro.', 500);
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'predict_heat':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'ID do animal não fornecido');
                
                $result = predictHeatCycle($db, $animal_id);
                sendResponse($result);
                break;
                
            case 'predict_production':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'ID do animal não fornecido');
                
                $days_ahead = $_GET['days_ahead'] ?? 7;
                $result = predictProduction($db, $animal_id, $days_ahead);
                sendResponse($result);
                break;
                
            case 'detect_anomalies':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'ID do animal não fornecido');
                
                $result = detectProductionAnomalies($db, $animal_id);
                sendResponse($result);
                break;
                
            case 'recommendations':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'ID do animal não fornecido');
                
                $result = generateSmartRecommendations($db, $animal_id);
                sendResponse($result);
                break;
                
            case 'batch_predictions':
                // Gerar previsões para todos animais ativos
                $animals = $db->query("
                    SELECT id FROM animals 
                    WHERE is_active = 1 
                      AND status IN ('Lactante', 'Vaca')
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                $results = [
                    'total_animals' => count($animals),
                    'heat_predictions' => 0,
                    'production_predictions' => 0,
                    'errors' => 0
                ];
                
                foreach ($animals as $animal) {
                    try {
                        // Prever cio
                        $heat_result = predictHeatCycle($db, $animal['id']);
                        if ($heat_result['success']) {
                            $results['heat_predictions']++;
                        }
                        
                        // Prever produção
                        $prod_result = predictProduction($db, $animal['id'], 7);
                        if ($prod_result['success']) {
                            $results['production_predictions']++;
                        }
                    } catch (Exception $e) {
                        $results['errors']++;
                        error_log("Erro previsão animal {$animal['id']}: " . $e->getMessage());
                    }
                }
                
                sendResponse($results);
                break;
                
            case 'farm_insights':
                // Insights gerais da fazenda
                $insights = [];
                
                // 1. Animais com BCS crítico
                $critical_bcs = $db->query("
                    SELECT COUNT(DISTINCT a.id) as count
                    FROM animals a
                    INNER JOIN body_condition_scores bcs ON a.id = bcs.animal_id
                    INNER JOIN (
                        SELECT animal_id, MAX(evaluation_date) as max_date
                        FROM body_condition_scores GROUP BY animal_id
                    ) latest ON bcs.animal_id = latest.animal_id AND bcs.evaluation_date = latest.max_date
                    WHERE bcs.score < 2.0 AND a.is_active = 1
                ")->fetch(PDO::FETCH_ASSOC);
                
                if ($critical_bcs['count'] > 0) {
                    $insights[] = [
                        'category' => 'nutrition',
                        'priority' => 'critical',
                        'title' => 'BCS Crítico',
                        'message' => "{$critical_bcs['count']} animais com BCS < 2.0. Ação urgente necessária!",
                        'count' => $critical_bcs['count']
                    ];
                }
                
                // 2. Animais com queda de produção
                $production_drops = $db->query("
                    SELECT COUNT(*) as count
                    FROM (
                        SELECT 
                            mp.animal_id,
                            AVG(CASE WHEN mp.production_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY) THEN mp.volume END) as recent,
                            AVG(CASE WHEN mp.production_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN mp.volume END) as old
                        FROM milk_production mp
                        INNER JOIN animals a ON mp.animal_id = a.id
                        WHERE a.is_active = 1
                        GROUP BY mp.animal_id
                        HAVING old > 0 AND ((old - recent) / old) > 0.20
                    ) drops
                ")->fetch(PDO::FETCH_ASSOC);
                
                if ($production_drops['count'] > 0) {
                    $insights[] = [
                        'category' => 'production',
                        'priority' => 'high',
                        'title' => 'Queda de Produção',
                        'message' => "{$production_drops['count']} animais com queda > 20% na produção. Investigar causas.",
                        'count' => $production_drops['count']
                    ];
                }
                
                // 3. Partos iminentes (< 7 dias)
                $imminent_calvings = $db->query("
                    SELECT COUNT(*) as count
                    FROM pregnancy_controls pc
                    INNER JOIN animals a ON pc.animal_id = a.id
                    WHERE DATEDIFF(pc.expected_birth, CURDATE()) BETWEEN 0 AND 7
                      AND a.is_active = 1
                ")->fetch(PDO::FETCH_ASSOC);
                
                if ($imminent_calvings['count'] > 0) {
                    $insights[] = [
                        'category' => 'reproduction',
                        'priority' => 'urgent',
                        'title' => 'Partos nos Próximos 7 Dias',
                        'message' => "{$imminent_calvings['count']} partos previstos. Preparar maternidade.",
                        'count' => $imminent_calvings['count']
                    ];
                }
                
                // 4. Medicamentos em estoque baixo
                $low_stock = $db->query("
                    SELECT COUNT(*) as count
                    FROM medications
                    WHERE stock_quantity <= min_stock AND is_active = 1
                ")->fetch(PDO::FETCH_ASSOC);
                
                if ($low_stock['count'] > 0) {
                    $insights[] = [
                        'category' => 'inventory',
                        'priority' => 'medium',
                        'title' => 'Estoque Baixo de Medicamentos',
                        'message' => "{$low_stock['count']} medicamentos com estoque baixo. Repor urgente.",
                        'count' => $low_stock['count']
                    ];
                }
                
                sendResponse([
                    'total_insights' => count($insights),
                    'insights' => $insights,
                    'generated_at' => date('Y-m-d H:i:s')
                ]);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'run_daily_ai':
                // Executar todas as rotinas de IA diárias
                $results = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'tasks' => []
                ];
                
                // 1. Atualizar cache de ações
                try {
                    $db->query("CALL refresh_action_lists()");
                    $results['tasks'][] = ['name' => 'refresh_action_lists', 'status' => 'success'];
                } catch (Exception $e) {
                    $results['tasks'][] = ['name' => 'refresh_action_lists', 'status' => 'error', 'message' => $e->getMessage()];
                }
                
                // 2. Gerar previsões de cio para animais elegíveis
                $eligible = $db->query("
                    SELECT DISTINCT a.id
                    FROM animals a
                    INNER JOIN heat_cycles hc ON a.id = hc.animal_id
                    WHERE a.is_active = 1
                      AND a.reproductive_status = 'vazia'
                      AND a.status IN ('Lactante', 'Vaca', 'Novilha')
                    GROUP BY a.id
                    HAVING COUNT(hc.id) >= 2
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                $heat_count = 0;
                foreach ($eligible as $animal) {
                    try {
                        $result = predictHeatCycle($db, $animal['id']);
                        if ($result['success']) $heat_count++;
                    } catch (Exception $e) {
                        error_log("Erro previsão cio: " . $e->getMessage());
                    }
                }
                $results['tasks'][] = ['name' => 'heat_predictions', 'status' => 'success', 'count' => $heat_count];
                
                // 3. Gerar previsões de produção
                $prod_animals = $db->query("
                    SELECT DISTINCT a.id
                    FROM animals a
                    INNER JOIN milk_production mp ON a.id = mp.animal_id
                    WHERE a.is_active = 1
                      AND a.status = 'Lactante'
                      AND mp.production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY a.id
                    HAVING COUNT(DISTINCT mp.production_date) >= 7
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                $prod_count = 0;
                foreach ($prod_animals as $animal) {
                    try {
                        $result = predictProduction($db, $animal['id'], 7);
                        if ($result['success']) $prod_count++;
                    } catch (Exception $e) {
                        error_log("Erro previsão produção: " . $e->getMessage());
                    }
                }
                $results['tasks'][] = ['name' => 'production_predictions', 'status' => 'success', 'count' => $prod_count];
                
                sendResponse($results);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
} catch (Exception $e) {
    error_log("Erro AI Engine: " . $e->getMessage());
    sendResponse(null, 'Erro: ' . $e->getMessage(), 500);
}

